<?php

namespace Edge\Mail\Api\Transport;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MailGunFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return MailGun
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config  = $serviceLocator->get('Config');
        $config  = isset($config['edge']['mailgun']) ? $config['edge']['mailgun'] : null;

        return new MailGun(new MailGunOptions($config));
    }
}