<?php

namespace Edge\Doctrine\Fixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader as DoctrineLoader;
use Doctrine\ORM\EntityManager;

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

    public function __construct(EntityManager $em, array $paths)
    {
        $this->entityManager = $em;
        $this->paths         = $paths;
    }

    public function loadAllFixtures()
    {
        $executor      = new ORMExecutor($this->entityManager);
        $fixtureLoader = new DoctrineLoader();

        foreach ($this->paths as $path) {
            $fixtureLoader->loadFromDirectory($path);
        }

        $executor->execute($fixtureLoader->getFixtures(), true);
    }
}