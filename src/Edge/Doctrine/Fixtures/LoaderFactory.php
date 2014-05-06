<?php

namespace Edge\Doctrine\Fixtures;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LoaderFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return Loader
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $paths  = $config['edge']['doctrine']['fixtures'];

        return new Loader($serviceLocator->get('EntityManager'), $paths);
    }
}