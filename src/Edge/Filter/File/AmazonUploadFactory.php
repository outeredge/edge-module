<?php

namespace Edge\Filter\File;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AmazonUploadFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return AmazonUpload
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new AmazonUpload($serviceLocator->get('Edge\Service\Aws\S3'));
    }
}
