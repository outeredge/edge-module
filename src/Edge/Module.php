<?php

namespace Edge;

class Module
{
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Edge\Rest\JsonRenderer' => function ($services) {
                    $config  = $services->get('Config');

                    $displayExceptions = false;
                    if (isset($config['view_manager'])
                        && isset($config['view_manager']['display_exceptions'])
                    ) {
                        $displayExceptions = (bool) $config['view_manager']['display_exceptions'];
                    }

                    $renderer = new Rest\View\RestfulJsonRenderer();
                    $renderer->setDisplayExceptions($displayExceptions);

                    return $renderer;
                },
                'Edge\Rest\JsonStrategy' => function ($services) {
                    $renderer = $services->get('Edge\Rest\JsonRenderer');
                    return new Rest\View\RestfulJsonStrategy($renderer);
                },
            )
        );
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}