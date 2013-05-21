<?php

namespace Edge\Filter\File;

use Zend\Filter\AbstractFilter;
use Edge\Service\Aws\S3;
use Edge\Service\Exception\ExceptionInterface;

class AmazonUpload extends AbstractFilter
{
    /**
     * @var S3
     */
    protected $s3service;

    /**
     * @var array
     */
    protected $options = array(
        'path'            => null,
        'return_metadata' => true
    );

    public function __construct(S3 $s3service)
    {
        $this->s3service = $s3service;
    }

    /**
     * Set the upload target path (prepended to filename)
     *
     * @param  string $path
     * @return AmazonUpload
     */
    public function setPath($path)
    {
        $this->options['path'] = $path;
        return $this;
    }

    /**
     * @return string Upload target path
     */
    public function getPath()
    {
        return trim($this->options['path'], '/');
    }

    /**
     * Set whether to return full metadata
     *
     * @param bool $return
     * @return AmazonUpload
     */
    public function setReturnMetadata($return)
    {
        $this->options['return_metadata'] = $return;
        return $this;
    }

    /**
     * @return bool When false, only the new path will be returned as a string
     *              instead of an array of metadata (i.e. type, size, tmp_name)
     */
    public function getReturnMetadata()
    {
        return $this->options['return_metadata'];
    }

    public function filter($value)
    {
        $destination = $this->getPath() . '/' . basename($value['tmp_name']);

        $this->uploadFile($value, $destination);

        if (!$this->getReturnMetadata()) {
            return $destination;
        }

        $value['tmp_name'] = $destination;

        return $value;
    }

    protected function uploadFile(array $file, $destination)
    {
        try {
            $this->getAmazonS3Service()->uploadFile($file['tmp_name'], $destination, $file['type']);
            @unlink($file['tmp_name']);
        } catch (ExceptionInterface $ex) {
            @unlink($file['tmp_name']);
            throw $ex;
        }
    }

    /**
     * @return S3
     */
    public function getAmazonS3Service()
    {
        return $this->s3service;
    }
}