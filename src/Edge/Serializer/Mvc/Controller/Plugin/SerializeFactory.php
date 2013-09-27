<?php

namespace Edge\Serializer\Mvc\Controller\Plugin;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Serialize($plugins->getServiceLocator()->get('Edge\Serializer\Serializer'));
    }
}