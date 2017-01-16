<?php

namespace Edge\Doctrine\Fixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader as DoctrineLoader;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

class Loader
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    public function __construct(EntityManager $em, array $paths, ContainerInterface $container = null)
    {
        $this->entityManager = $em;
        $this->paths         = $paths;
        $this->container     = $container;
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
            if ($fixture instanceof LoaderContainerAwareInterface) {
                $fixture->setContainer($this->container);
            }
        }

        $executor->execute($fixtureLoader->getFixtures(), true);
    }
}