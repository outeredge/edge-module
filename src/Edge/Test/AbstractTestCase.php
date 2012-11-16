<?php

namespace Edge\Test;

use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use PHPUnit_Framework_TestCase;

abstract class AbstractTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @var array
     */
    protected static $applicationConfig;

    protected function setUp()
    {
        $configuration = self::$applicationConfig;
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
        $this->serviceManager = new ServiceManager(new ServiceManagerConfig($smConfig));
        $this->serviceManager->setService('ApplicationConfig', $configuration);
        $this->serviceManager->get('ModuleManager')->loadModules();
    }

    public static function setApplicationConfig($applicationConfig)
    {
        self::$applicationConfig = $applicationConfig;
    }
}