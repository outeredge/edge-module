<?php

namespace Edge\Doctrine\Fixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader as DoctrineLoader;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Loader
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var array
     */
    protected $paths;

    public function __construct(EntityManager $em, ServiceLocatorInterface $serviceLocator, array $paths)
    {
        $this->entityManager  = $em;
        $this->serviceLocator = $serviceLocator;
        $this->paths          = $paths;
    }

    public function loadAllFixtures()
    {
        $executor      = new ORMExecutor($this->entityManager);
        $fixtureLoader = new DoctrineLoader();

        foreach ($this->paths as $path) {
            $fixtureLoader->loadFromDirectory($path);
        }

        $fixtures = $fixtureLoader->getFixtures();

        foreach ($fixtures as $fixture) {
            if ($fixture instanceof ServiceLocatorAwareInterface) {
                $fixture->setServiceLocator($this->serviceLocator);
            }
        }

        $executor->execute($fixtures, true);
    }
}