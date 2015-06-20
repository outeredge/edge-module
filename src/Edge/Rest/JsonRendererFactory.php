<?php

namespace Edge\Rest;

use Edge\Rest\View\RestfulJsonRenderer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JsonRendererFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return RestfulJsonRenderer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config  = $serviceLocator->get('Config');

        $displayExceptions = false;
        if (isset($config['view_manager'])
            && isset($config['view_manager']['display_exceptions'])
        ) {
            $displayExceptions = (bool) $config['view_manager']['display_exceptions'];
        }

        $renderer = new RestfulJsonRenderer();
        $renderer->setDisplayExceptions($displayExceptions);

        return $renderer;
    }
}