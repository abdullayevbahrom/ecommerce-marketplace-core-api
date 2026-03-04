<?php

declare(strict_types=1);

namespace app\commands;

use app\components\S3Component;
use app\models\Images;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;
use yii\imagine\Image;

class MinioController extends Controller
{
    public int $batch = 100;
    public string $root = '@app/web/uploads';
    public int $generateMissing = 1;
    public array $sizes = ['50x50', '100x100', '150x150', '200x200', '250x250', '300x300'];
    public string $originalDir = 'original';
    public int $quality = 80;
    public int $limit = 0;        // 0 = no limit
    public int $fromId = 0;       // start id
    public ?string $type = null;  // filter by type
    public int $dryRun = 0;       // 1 = do not upload / do not update
    public int $skipExisting = 1;
    public int $cleanup = 0;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'batch',
            'limit',
            'fromId',
            'type',
            'root',
            'sizes',
            'originalDir',
            'quality',
            'dryRun',
            'generateMissing',
            'skipExisting',
            'cleanup',
        ]);
    }

    public function actionMigrateImages(): int
    {
        /** @var S3Component $s3 */
        $s3 = \Yii::$app->s3;

        $q = Images::find()
            ->andWhere(['web' => 0])
            ->andWhere(['not', ['photo' => null]])
            ->andWhere(['not', ['object_id' => null]])
            ->orderBy(['id' => SORT_ASC]);

        if ($this->fromId > 0) {
            $q->andWhere(['>=', 'id', $this->fromId]);
        }
        if ($this->type) {
            $q->andWhere(['type' => $this->type]);
        }
        if ($this->limit > 0) {
            $q->limit($this->limit);
        }

        $total = (clone $q)->count();
        $this->stdout("Found {$total} images to migrate (web=0)\n");

        $done = 0;
        foreach ($q->batch($this->batch) as $rows) {
            foreach ($rows as $img) {
                /** @var Images $img */
                $done++;

                $id = (int)$img->id;
                $type = (string)$img->type;
                $objectId = (string)$img->object_id;
                $fileName = (string)$img->photo;

                $sizes = $this->buildSizes($img);

                $originalLocal = $this->localPath($type, $objectId, 'original', $fileName);
                if (!is_file($originalLocal)) {
                    $this->stderr("#{$id} SKIP: local original not found: {$originalLocal}\n");
                    continue;
                }

                $this->stdout("#{$id} {$type}/{$objectId} {$fileName}\n");

                if ($this->dryRun) {
                    $this->stdout("  DRY RUN: would upload original + thumbs and set web=1\n");
                    continue;
                }

                $contentType = @mime_content_type($originalLocal) ?: 'application/octet-stream';
                $s3->putVariant($type, $objectId, 'original', $fileName, $originalLocal, $contentType);

                foreach ($sizes as $size) {
                    if ($size === 'original') continue;

                    $p = $this->localPath($type, $objectId, $size, $fileName);
                    if (!is_file($p)) {
                        continue;
                    }
                    $s3->putVariant($type, $objectId, $size, $fileName, $p);
                }

                \Yii::$app->db->createCommand()->update(Images::tableName(), [
                    'web' => 1,
                ], ['id' => $id])->execute();
            }
        }

        $this->stdout("DONE. Migrated: {$done}\n");
        return ExitCode::OK;
    }

    private function localPath(string $type, string $objectId, string $size, string $fileName): string
    {
        $base = rtrim(\Yii::getAlias($this->root), '/'); // @app/web/uploads

        return "{$base}/{$type}/{$objectId}/{$size}/{$fileName}";
    }

    private function buildSizes(Images $img): array
    {
        $sizes = ['original'];

        foreach ($img->image_sizes as $w => $h) {
            $sizes[] = "{$w}x{$h}";
        }

        return $sizes;
    }

    private function key(string $model, string $objectId, string $size, string $fileName): string
    {
        $model = trim($model, '/');
        $objectId = trim($objectId, '/');
        $size = trim($size, '/');
        $fileName = ltrim($fileName, '/');

        return "{$model}/{$objectId}/{$size}/{$fileName}";
    }

    public function actionSyncUploads(): int
    {
        /** @var S3Component $s3 */
        $s3 = \Yii::$app->s3;

       $root = rtrim(\Yii::getAlias($this->root), '/');
        if (!is_dir($root)) {
            $this->stderr("Uploads root not found: {$root}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (is_string($this->sizes)) {
            $this->sizes = array_values(array_filter(array_map('trim', explode(',', $this->sizes))));
        }

        $this->stdout("Root: {$root}\n");
        $this->stdout("Sizes: " . implode(', ', $this->sizes) . "\n");
        $this->stdout("Original dir: {$this->originalDir}\n");
        $this->stdout("DryRun: {$this->dryRun}\n");
        $this->stdout("GenerateMissing: {$this->generateMissing}\n");

        $models = FileHelper::findDirectories($root, ['recursive' => false]);
        $totalPairs = 0;
        $uploadedCount = 0;
        $generatedCount = 0;
        $skippedCount = 0;
        $this->stdout("Found " . count($models) . " model directories\n");

        foreach ($models as $modelDir) {
            $model = basename($modelDir);

            $ids = FileHelper::findDirectories($modelDir, ['recursive' => false]);
            foreach ($ids as $idDir) {
                $objectId = basename($idDir);
                $totalPairs++;

                $filesMap = $this->collectFilesBySizes($idDir);

                if (!$filesMap) {
                    continue;
                }

                $this->stdout("\n== {$model}/{$objectId} (" . count($filesMap) . " files)\n");

                foreach ($filesMap as $fileName => $bySize) {
                    $source = $this->pickSource($bySize);

                    if (!$source) {
                        $this->stderr("  - {$fileName}: SKIP (no source found)\n");
                        $skippedCount++;
                        continue;
                    }

                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $isVectorOrVideo = in_array($ext, ['svg', 'mp4', 'webm', 'mov'], true);

                    $originalLocal = $bySize[$this->originalDir] ?? $source;

                    $uploadedCount += $this->uploadVariant($s3, $model, $objectId, $this->originalDir, $fileName, $originalLocal);

                    if ($isVectorOrVideo) {
                        foreach ($this->sizes as $size) {
                            if (!empty($bySize[$size]) && is_file($bySize[$size])) {
                                $uploadedCount += $this->uploadVariant($s3, $model, $objectId, $size, $fileName, $bySize[$size]);
                            }
                        }
                        continue;
                    }

                    foreach ($this->sizes as $size) {
                        if (!empty($bySize[$size]) && is_file($bySize[$size])) {
                            $uploadedCount += $this->uploadVariant($s3, $model, $objectId, $size, $fileName, $bySize[$size]);
                            continue;
                        }

                        if (!$this->generateMissing) {
                            continue;
                        }

                        [$w, $h] = $this->parseSize($size);
                        if (!$w || !$h) {
                            $this->stderr("  - {$fileName}: bad size format: {$size}\n");
                            continue;
                        }

                        $tmp = \Yii::getAlias('@runtime') . '/minio_thumb_' . $w . 'x' . $h . '_' . uniqid() . '_' . $fileName;

                        try {
                            Image::thumbnail($source, $w, $h)->save($tmp, ['quality' => $this->quality]);
                            $generatedCount++;
                            $uploadedCount += $this->uploadVariant($s3, $model, $objectId, $size, $fileName, $tmp);
                        } catch (\Throwable $e) {
                            $this->stderr("  - {$fileName}: thumb {$size} FAIL: {$e->getMessage()}\n");
                        } finally {
                            @unlink($tmp);
                        }
                    }
                }
            }
        }

        // if ($this->cleanup > 0 && !$this->dryRun) {
        //     foreach ($bySize as $sz => $p) {
        //         if (is_file($p)) {
        //             $this->cleanupLocalFile($p);
        //         }
        //     }

        //     if ($this->cleanup >= 2) {
        //         $this->cleanupEmptyDirsUp($idDir, \Yii::getAlias($this->root));
        //     }
        // }

        $this->stdout("\nDONE\n");
        $this->stdout("Pairs (model/id): {$totalPairs}\n");
        $this->stdout("Uploaded variants: {$uploadedCount}\n");
        $this->stdout("Generated thumbs: {$generatedCount}\n");
        $this->stdout("Skipped files: {$skippedCount}\n");

        return ExitCode::OK;
    }

    private function collectFilesBySizes(string $idDir): array
    {
        $sizesDirs = FileHelper::findDirectories($idDir, ['recursive' => false]);

        $map = [];
        foreach ($sizesDirs as $sizeDirPath) {
            $size = basename($sizeDirPath);

            $files = FileHelper::findFiles($sizeDirPath, [
                'recursive' => false,
                'only' => ['*.jpg', '*.jpeg', '*.png', '*.webp', '*.gif', '*.svg', '*.mp4', '*.webm', '*.mov'],
            ]);

            foreach ($files as $filePath) {
                $name = basename($filePath);
                $map[$name][$size] = $filePath;
            }
        }

        return $map;
    }

    private function pickSource(array $bySize): ?string
    {
        if (!empty($bySize[$this->originalDir]) && is_file($bySize[$this->originalDir])) {
            return $bySize[$this->originalDir];
        }

        $best = null;
        $bestArea = 0;
        foreach ($bySize as $size => $path) {
            if (!is_file($path)) continue;
            if ($size === $this->originalDir) continue;

            if (preg_match('~^(\d+)x(\d+)$~', $size, $m)) {
                $area = ((int)$m[1]) * ((int)$m[2]);
                if ($area > $bestArea) {
                    $bestArea = $area;
                    $best = $path;
                }
            } else {
                $best = $best ?: $path;
            }
        }

        return $best;
    }

    private function parseSize(string $size): array
    {
        if (!preg_match('~^(\d+)x(\d+)$~', $size, $m)) {
            return [0, 0];
        }
        return [(int)$m[1], (int)$m[2]];
    }

    private function uploadVariant(S3Component $s3, string $model, string $objectId, string $size, string $fileName, string $localPath): int
    {
        if (!is_file($localPath)) {
            return 0;
        }

        $key = $this->key($model, $objectId, $size, $fileName);

        if ($this->skipExisting) {
            try {
                if ($s3->exists($key)) {
                    $this->stdout("  SKIP (exists): {$size}/{$fileName}\n");
                    return 0;
                }
            } catch (\Throwable $e) {
                $this->stdout("  WARN headObject failed for {$key}: {$e->getMessage()}\n");
            }
        }

        $contentType = @mime_content_type($localPath) ?: 'application/octet-stream';

        if ($this->dryRun) {
            $this->stdout("  DRY: upload {$key} <- {$localPath}\n");
            return 0;
        }

        try {
            $s3->putFile($key, $localPath, $contentType);
            $this->stdout("  OK: {$size}/{$fileName}\n");
            return 1;
        } catch (\Throwable $e) {
            $this->stderr("  FAIL: {$size}/{$fileName} -> {$e->getMessage()}\n");
            return 0;
        }
    }

    private function cleanupLocalFile(?string $path): void
    {
        if (!$path) return;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function cleanupEmptyDirsUp(string $dir, string $stopAt): void
    {
        $dir = rtrim($dir, '/');
        $stopAt = rtrim($stopAt, '/');

        while (strlen($dir) >= strlen($stopAt) && strpos($dir, $stopAt) === 0) {
            if (!is_dir($dir)) break;

            $items = @scandir($dir);
            if ($items && count($items) === 2) {
                @rmdir($dir);
                $dir = dirname($dir);
                continue;
            }
            break;
        }
    }
}
