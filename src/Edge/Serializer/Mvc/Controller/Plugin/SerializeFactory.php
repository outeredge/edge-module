<?php

namespace Edge\Serializer\Mvc\Controller\Plugin;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Serialize($serviceLocator->get('Edge\Serializer\Serializer'));
    }
}