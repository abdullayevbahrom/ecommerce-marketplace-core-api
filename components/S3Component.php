<?php

namespace app\components;

use Yii;
use Aws\S3\S3Client;
use yii\base\Component;

class S3Component extends Component
{
    private $client;
    private $bucket;
    private $baseUrl;

    public function init()
    {
        parent::init(); 

        $config = Yii::$app->params['minio'];   

        $this->bucket = $config['bucket'];
        $this->baseUrl = rtrim($config['publicEndpoint'], '/'); 

        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => $config['region'], 

            'endpoint' => $config['publicEndpoint'],
            'use_path_style_endpoint' => true,  

            'credentials' => [
                'key'    => $config['user'],
                'secret' => $config['password'],
            ],
        ]);
    }


    public function upload($key, $sourcePath)
    {
        return $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => fopen($sourcePath, 'r'),
            'ContentType' => mime_content_type($sourcePath),
        ]);
    }

    public function getUrl($key)
    {
        return $this->baseUrl . '/' . $key;
    }

    public function deleteObject(string $key): bool
    {
        try {
            // Проверяем существует ли файл
            if (!$this->client->doesObjectExist($this->bucket, $key)) {
                Yii::warning("MinIO: File not found for delete: {$key}", __METHOD__);
                return false;
            }
    
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
    
            return true;
    
        } catch (\Throwable $e) {
            Yii::error("MinIO delete error: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

}
