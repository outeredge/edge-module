<?php

namespace Edge\Rest;

use Edge\Rest\View\RestfulJsonRenderer;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config  = $container->get('Config');

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