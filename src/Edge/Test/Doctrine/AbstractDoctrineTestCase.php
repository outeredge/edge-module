<?php

namespace Edge\Test\Doctrine;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Loader;
use Edge\Test\AbstractTestCase;

abstract class AbstractDoctrineTestCase extends AbstractTestCase
{
    public static $schemaExists = false;

    protected $entityManagerAlias = 'EntityManager';

    protected $platform;

    protected $database;

    protected $provider;

    protected function setUp()
    {
        if (self::$schemaExists) {
            if (!$this->getProvider()->restoreDatabase($this->getDatabaseName())) {
                $this->loadFixtures(false);
            }
            return;
        }

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->getProvider()->deleteDatabase($this->getDatabaseName());

        $schemaTool = new SchemaTool($this->getEntityManager());
        $schemaTool->createSchema($this->getEntityManager()->getMetadataFactory()->getAllMetadata());

        $this->loadFixtures();

        $this->getProvider()->backupDatabase($this->getDatabaseName());

        self::$schemaExists = true;
    }

    protected function loadFixtures($append = true)
    {
        $fixtureLoader = new Loader();
        $fixtureLoader->loadFromDirectory($this->getFixturePath());

        if ($append) {
            $purger = null;
        } else {
            $purger = new ORMPurger();
        }

        $executor = new ORMExecutor($this->getEntityManager(), $purger);
        $executor->execute($fixtureLoader->getFixtures(), $append);
    }

    protected function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = ucfirst($this->getEntityManager()->getConnection()->getDatabasePlatform()->getName());
        }
        return $this->platform;
    }

    protected function getDatabaseName()
    {
        if (null === $this->database) {
            $this->database = $this->getEntityManager()->getConnection()->getDatabase();
        }
        return $this->database;
    }

    /**
     * @return Provider\ProviderInterface
     */
    protected function getProvider()
    {
        if (null == $this->provider) {
            $providerClass = 'Edge\\Test\\Doctrine\\Provider\\' . $this->getPlatform();
            if (!class_exists($providerClass)) {
                throw new \Exception('Unsupported database provider type');
            }
            $this->provider = new $providerClass;
            $this->provider->setEntityManager($this->getEntityManager());
        }
        return $this->provider;
    }

    protected function tearDown()
    {
        $this->getEntityManager()->clear();
    }

    public function getRepository($entityClass)
    {
        return $this->getEntityManager()->getRepository($entityClass);
    }

    /**
     * Get a fixture by using a simple {find} on the EM
     *
     * @param string $class
     * @param int $id
     */
    protected function getFixture($class, $id)
    {
        return $this->getEntityManager()->find($class, $id);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getServiceManager()->get($this->entityManagerAlias);
    }

    /**
     * @return string
     */
    abstract protected function getFixturePath();
}