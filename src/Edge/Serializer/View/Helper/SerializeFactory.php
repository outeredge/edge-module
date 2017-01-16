<?php

namespace Edge\Serializer\View\Helper;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SerializeFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Serializer
     */
    public function createService(ServiceLocatorInterface $views)
    {
        return new Serialize($views->getServiceLocator()->get('Edge\Serializer\Serializer'));
    }
}