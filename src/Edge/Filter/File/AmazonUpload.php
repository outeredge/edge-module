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

    public function __construct(S3 $s3service)
    {
        $this->s3service = $s3service;
    }

    public function filter($value)
    {
        $destination = basename($value['tmp_name']);
        
        $this->uploadFile($value, $destination);

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