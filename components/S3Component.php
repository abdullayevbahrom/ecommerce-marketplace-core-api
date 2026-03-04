<?php

namespace app\components;

use Yii;
use Aws\S3\S3Client;
use yii\base\Component;

class S3Component extends Component
{
    private S3Client $client;
    private string $bucket;
    private string $baseUrl;

    public function init()
    {
        parent::init();

        $config = Yii::$app->params['minio'];

        $this->bucket = $config['bucket'];
        $this->baseUrl = rtrim($config['publicEndpoint'], '/');

        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => $config['region'],

            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => true,

            'credentials' => [
                'key'    => $config['user'],
                'secret' => $config['password'],
            ],
        ]);
    }

    public function putFile(string $key, string $localPath, ?string $contentType = null): array
    {
        $body = fopen($localPath, 'rb');
        if ($body === false) {
            throw new \RuntimeException("Can not open file: {$localPath}");
        }

        $contentType = $contentType ?: (function () use ($localPath) {
            $t = @mime_content_type($localPath);

            return $t ?: 'application/octet-stream';
        })();

        try {
            $res = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ContentType' => $contentType,
            ]);
        } catch (\Throwable $e) {
            Yii::error("MinIO upload error: " . $e->getMessage(), __METHOD__);
            throw $e;
        } finally {
            if (\is_resource($body)) fclose($body);
        }

        return $res->toArray();
    }

    public function putVariant(string $type, string $objectId, string $size, string $fileName, string $localPath, ?string $contentType = null): array
    {
        $key = $this->buildKey($type, $objectId, $size, $fileName);

        return $this->putFile($key, $localPath, $contentType);
    }

    public function delete(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if ($e->getAwsErrorCode() === 'NoSuchKey') {
                return false;
            }

            throw $e;
        } catch (\Throwable $e) {
            Yii::error("MinIO delete error: {$e->getMessage()} key={$key}", __METHOD__);
            return false;
        }
    }

    public function deleteVariants(string $type, string $objectId, string $fileName, array $sizes): void
    {
        foreach ($sizes as $size) {
            $key = $this->buildKey($type, $objectId, $size, $fileName);
            $this->delete($key);
        }
    }

    public function url(string $key): string
    {
        return $this->baseUrl . '/' . $this->bucket . '/' . ltrim($key, '/');
    }

    public function urlVariant(string $type, string $objectId, string $size, string $fileName): string
    {
        return $this->url($this->buildKey($type, $objectId, $size, $fileName));
    }

    public function exists(string $key): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (S3Exception $e) {
            $code = (string)$e->getAwsErrorCode();
            $status = (int)$e->getStatusCode();

            if ($code === 'NotFound' || $code === 'NoSuchKey' || $status === 404) {
                return false;
            }
            throw $e;
        }
    }

    public function putFileVariant(string $type, string $objectId, string $fileName, string $localPath, ?string $contentType = null): array
    {
        return $this->putVariant($type, $objectId, 'file', $fileName, $localPath, $contentType);
    }

    public function urlFileVariant(string $type, string $objectId, string $fileName): string
    {
        return $this->urlVariant($type, $objectId, 'file', $fileName);
    }

    public function deleteFileVariant(string $type, string $objectId, string $fileName): bool
    {
        $key = $this->buildKey($type, $objectId, 'file', $fileName);
        
        return $this->delete($key);
    }

    private function buildKey(string $type, string $objectId, string $size, string $fileName): string
    {
        $type = trim($type, '/');
        $objectId = trim($objectId, '/');
        $size = trim($size, '/');
        $fileName = ltrim($fileName, '/');

        return "{$type}/{$objectId}/{$size}/{$fileName}";
    }
}
