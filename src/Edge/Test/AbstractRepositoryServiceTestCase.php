<?php

namespace Edge\Test;

use Edge\Service\AbstractRepositoryService;
use PHPUnit_Framework_ExpectationFailedException;

abstract class AbstractRepositoryServiceTestCase extends AbstractTestCase
{
    /**
     * @var AbstractRepositoryService
     */
    private $service;

    /**
     * Set the current service
     *
     * @param AbstractRepositoryService $service
     */
    protected function setService(AbstractRepositoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Get the current service
     *
     * @return AbstractRepositoryService
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * Assert that an entity was returned from a create action
     *
     * @param string $entityClass
     * @param mixed  $result
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function assertEntityCreated($entityClass, $result)
    {
        if (!$result instanceof $entityClass) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting that a "%s" entity was created: ' . "\n%s",
                $entityClass,
                print_r($this->getService()->getErrorMessages(), true)
            ));
        }
        $this->assertInstanceOf($entityClass, $result);
    }

    /**
     * Assert that an entity was returned from an update action
     *
     * @param string $entityClass
     * @param mixed  $result
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function assertEntityUpdated($entityClass, $result)
    {
        if (!$result instanceof $entityClass) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting that a "%s" entity was updated: ' . "\n%s",
                $entityClass,
                print_r($this->getService()->getErrorMessages(), true)
            ));
        }
        $this->assertInstanceOf($entityClass, $result);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->service);
    }
}