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
        parent::setUp();

        $this->application = $this->serviceManager->get('Application');
        $this->application->bootstrap();
    }
}