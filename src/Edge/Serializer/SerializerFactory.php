<?php

namespace Edge\Serializer;

use JMS\Serializer\SerializerBuilder;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SerializerFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Serializer(SerializerBuilder::create()->build());
    }
}