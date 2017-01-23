<?php

namespace Edge\Serializer;

use JMS\Serializer\SerializerBuilder;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SerializerFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config  = $container->get('config');
        $builder = SerializerBuilder::create();

        if (isset($config['edge']['serializer']['cache_dir'])) {
            $builder->setCacheDir($config['edge']['serializer']['cache_dir']);
        }

        $builder->setDebug($config['edge']['serializer']['debug']);

        return new Serializer($builder->build());
    }
}
