<?php

namespace Edge\Test;

abstract class AbstractMvcTestCase extends AbstractTestCase
{
    /**
     * @var \Zend\Mvc\Application
     */
    protected $application;

    protected function setUp()
    {
        $this->application = $this->getServiceManager()->get('Application');
        $this->application->bootstrap();
    }
}