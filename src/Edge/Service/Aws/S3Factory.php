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
        $config = isset($config['edge']['aws']['s3']) ? $config['edge']['aws']['s3'] : array();

        return new S3($serviceLocator->get(Sdk::class)->createS3(), $config);
    }
}