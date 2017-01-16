<?php

namespace Edge\Filter\File;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class AmazonUploadFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return AmazonUpload
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AmazonUpload($container->get('Edge\Service\Aws\S3'));
    }
}
