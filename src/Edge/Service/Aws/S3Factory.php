<?php

namespace Edge\Service\Aws;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class S3Factory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return S3
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        
        return new S3($serviceLocator->get('aws')->get('s3'), $config['edge']['aws']['s3']);
    }
}