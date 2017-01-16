<?php

namespace Edge\Serializer\Mvc\Controller\Plugin;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serialize
     */
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Serialize($plugins->getServiceLocator()->get('Edge\Serializer\Serializer'));
    }
}