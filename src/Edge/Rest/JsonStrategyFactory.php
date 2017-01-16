<?php

namespace Edge\Rest;

use Edge\Rest\View\RestfulJsonStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonStrategyFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return RestfulJsonStrategy
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $renderer = $container->get('Edge\Rest\JsonRenderer');
        return new RestfulJsonStrategy($renderer);
    }
}