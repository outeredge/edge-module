<?php

namespace Edge\Rest;

use Edge\Rest\View\RestfulJsonStrategy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JsonStrategyFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return RestfulJsonStrategy
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $renderer = $serviceLocator->get('Edge\Rest\JsonRenderer');
        return new RestfulJsonStrategy($renderer);
    }
}