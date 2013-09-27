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

    /**
     * @var array
     */
    protected $options = array(
        'bucket'        => null,
        'path_prefix'   => null,
        'storage_class' => Storage::STANDARD,
        'acl'           => CannedAcl::PRIVATE_ACCESS
    );

    public function __construct(S3Client $s3client, array $options)
    {
        $this->s3client = $s3client;
        $this->options  = array_merge($this->options, $options);
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
                    'Bucket' => $this->getBucket(),
                    'Key'    => $this->preparePath($path)
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
     * @param string $path
     * @param string $mime
     * @throws Exception\RuntimeException
     * @return void
     */
    public function uploadFile($file, $path, $mime = 'application/octet-stream')
    {
        try {
            $command = $this->getS3Client()->getCommand(
                'PutObject',
                array(
                    'Bucket'       => $this->getBucket(),
                    'Key'          => $this->preparePath($path),
                    'Body'         => EntityBody::factory(fopen($file, 'r')),
                    'ContentType'  => $mime,
                    'ACL'          => $this->options['acl'],
                    'StorageClass' => $this->options['storage_class'],
                )
            );
            $command->execute();
        } catch (S3Exception $ex) {
            throw new Exception\RuntimeException('Unable to upload file to Amazon S3', $ex->getCode(), $ex);
        }
    }

    /**
     * Get full meta-data for object
     *
     * @param  string $path
     * @throws Aws\S3\Exception\S3Exception when object does not exist
     * @return array
     */
    public function getFileInfo($path)
    {
        $headers = $this->getS3Client()->headObject(array(
            'Bucket' => $this->getBucket(),
            'Key'    => $this->preparePath($path),
        ));

        return $headers->toArray();
    }

    /**
     * Get file size of object
     *
     * @param string $file
     * @return int
     * @throws Aws\S3\Exception\S3Exception when object does not exist
     */
    public function getFileSize($file)
    {
        $info = $this->getFileInfo($file);
        return (int) $info['ContentLength'];
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
        $requestPath = $this->getBucket()
            . '/'
            . $this->preparePath($path)
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
     * Get stream handle for file
     *
     * @param string $file
     * @param string $mode
     * @return resource
     */
    public function getStreamHandle($path, $mode = 'r')
    {
        $this->getS3Client()->registerStreamWrapper();
        return fopen('s3://' . $this->getBucket() . '/' . $this->preparePath($path), $mode);
    }

    /**
     * Set bucket name
     *
     * @param string $bucket
     * @return self
     */
    public function setBucket($bucket)
    {
        $this->options['bucket'] = $bucket;
        return $this;
    }

    /**
     * Get bucket name
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->options['bucket'];
    }

    /**
     * Set the path prefix to be appended to all objext request
     *
     * @param string $prefix
     * @return self
     */
    public function setPathPrefix($prefix)
    {
        $this->options['path_prefix'] = $prefix;
        return $this;
    }

    /**
     * Get file path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->options['path_prefix'];
    }

    /**
     * Prepares an upload path by adding the prefix (if any)
     *
     * @param string $path
     * @return string
     */
    protected function preparePath($path)
    {
        return $this->getPathPrefix() . $path;
    }

    /**
     * Get Amazon S3 client service
     *
     * @return S3Client
     */
    public function getS3Client()
    {
        return $this->s3client;
    }
}