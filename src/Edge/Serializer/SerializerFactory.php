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
        $config  = $serviceLocator->get('Config');
        $builder = SerializerBuilder::create();

        if (isset($config['edge']['serializer']['cache_dir'])) {
            $builder->setCacheDir($config['edge']['serializer']['cache_dir']);
        }

        $builder->setDebug($config['edge']['serializer']['debug']);

        return new Serializer($builder->build());
    }
}
