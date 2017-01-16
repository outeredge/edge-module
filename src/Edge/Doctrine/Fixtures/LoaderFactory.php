<?php

namespace Edge\Doctrine\Fixtures;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LoaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $paths  = $config['edge']['doctrine']['fixtures'];

        return new Loader(
            $container->get('EntityManager'),
            $paths,
            $container
        );
    }
}