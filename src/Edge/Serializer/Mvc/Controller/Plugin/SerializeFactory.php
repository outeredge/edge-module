<?php

namespace Edge\Serializer\Mvc\Controller\Plugin;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serialize
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Serialize($container->get('Edge\Serializer\Serializer'));
    }
}