<?php

namespace Edge\Service\Aws;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class S3Factory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return S3
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $config = isset($config['edge']['aws']['s3']) ? $config['edge']['aws']['s3'] : array();

        return new S3($container->get(Sdk::class)->createS3(), $config);
    }
}