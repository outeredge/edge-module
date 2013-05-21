<?php

namespace Edge\Service\Aws;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\S3\Enum\CannedAcl;
use Aws\S3\Enum\Storage;
use Edge\Service\Exception;
use Guzzle\Http\EntityBody;

class S3
{
    /**
     * @var S3Client
     */
    protected $s3client;

    protected $bucket;

    public function __construct(S3Client $s3client, array $options)
    {
        $this->s3client = $s3client;

        if (isset($options['bucket'])) {
            $this->bucket = $options['bucket'];
        }
    }

    /**
     * Delete a file from Amazon S3
     *
     * @param string $path
     * @throws Exception\RuntimeException
     * @return void
     */
    public function deleteFile($path)
    {
        try {
            $command = $this->getS3Client()->getCommand(
                'DeleteObject',
                array(
                    'Bucket' => $this->bucket,
                    'Key'    => $path
                )
            );
            $command->execute();
        } catch (S3Exception $ex) {
            throw new Exception\RuntimeException('Unable to delete file on Amazon S3', $ex->getCode(), $ex);
        }
    }

    /**
     * Upload a file to Amazon S3
     *
     * @param string $file
     * @param string $uploadpath
     * @param string $mime
     * @throws Exception\RuntimeException
     * @return void
     */
    public function uploadFile($file, $uploadpath, $mime = 'application/octet-stream')
    {
        try {
            $command = $this->getS3Client()->getCommand(
                'PutObject',
                array(
                    'Bucket'       => $this->bucket,
                    'Key'          => $uploadpath,
                    'Body'         => EntityBody::factory(fopen($file, 'r')),
                    'ContentType'  => $mime,
                    'ACL'          => CannedAcl::AUTHENTICATED_READ,
                    'StorageClass' => Storage::STANDARD,
                )
            );
            $command->execute();
        } catch (S3Exception $ex) {
            throw new Exception\RuntimeException('Unable to upload file to Amazon S3', $ex->getCode(), $ex);
        }
    }

    /**
     * Get HTTPS download path for file
     *
     * @param string $path     path on remote server
     * @param string $realname original name of file
     * @return string
     */
    public function getDownloadPath($path, $realname)
    {
        $requestPath = $this->bucket
            . '/'
            . $path
            . '?response-content-disposition=attachment;'
            . 'filename="'
            . rawurlencode($realname)
            . '"';

        return $this->getS3Client()->createPresignedUrl(
            $this->getS3Client()->get($requestPath),
            '+30 minutes'
        );
    }

    /**
     * Get Amazon S3 client service
     *
     * @return S3Client
     */
    protected function getS3Client()
    {
        return $this->s3client;
    }
}