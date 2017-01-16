<?php

namespace Edge\Serializer\View\Helper;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Serialize($container->get('Edge\Serializer\Serializer'));
    }
}