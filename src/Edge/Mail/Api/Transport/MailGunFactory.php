<?php

namespace Edge\Mail\Api\Transport;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MailGunFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return MailGun
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config  = $container->get('Config');
        $config  = isset($config['edge']['mailgun']) ? $config['edge']['mailgun'] : null;

        return new MailGun(new MailGunOptions($config));
    }
}