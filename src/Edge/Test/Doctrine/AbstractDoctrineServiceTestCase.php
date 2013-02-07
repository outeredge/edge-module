<?php

namespace Edge\Test\Doctrine;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Loader;
use Edge\Test\AbstractRepositoryServiceTestCase;

abstract class AbstractDoctrineServiceTestCase extends AbstractRepositoryServiceTestCase
{
    /**
     * @var boolean
     */
    public static $schemaExists = false;

    /**
     * @var string
     */
    protected $entityManagerAlias = 'EntityManager';

    /**
     * @var string
     */
    protected $platform;

    /**
     * @var string
     */
    protected $database;

    /**
     * @var Provider\ProviderInterface
     */
    protected $provider;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Generate the database, will restore a fresh copy if the schema already exists
     *
     * @return AbstractDoctrineServiceTestCase
     */
    protected function generateDatabase()
    {
        if (self::$schemaExists) {
            if (!$this->getProvider()->restoreDatabase($this->getDatabaseName())) {
                $this->loadFixtures(false);
            }
            return $this;;
        }

        $this->createSchema();

        return $this;
    }

    private function createSchema()
    {
        $this->getProvider()->deleteDatabase($this->getDatabaseName());

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $this->loadFixtures();

        $this->getProvider()->backupDatabase($this->getDatabaseName());

        self::$schemaExists = true;
    }

    private function loadFixtures($append = true)
    {
        $fixtureLoader = new Loader();
        $fixtureLoader->loadFromDirectory($this->getFixturePath());

        if ($append) {
            $purger = null;
        } else {
            $purger = new ORMPurger();
        }

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($fixtureLoader->getFixtures(), $append);
    }

    private function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = ucfirst($this->entityManager->getConnection()->getDatabasePlatform()->getName());
        }
        return $this->platform;
    }

    private function getDatabaseName()
    {
        if (null === $this->database) {
            $this->database = $this->entityManager->getConnection()->getDatabase();
        }
        return $this->database;
    }

    /**
     * @return Provider\ProviderInterface
     */
    private function getProvider()
    {
        if (null == $this->provider) {
            $providerClass = 'Edge\\Test\\Doctrine\\Provider\\' . $this->getPlatform();
            if (!class_exists($providerClass)) {
                throw new \Exception('Unsupported database provider type');
            }
            $this->provider = new $providerClass;
            $this->provider->setEntityManager($this->entityManager);
        }
        return $this->provider;
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
    public function getFixture($class, $id)
    {
        return $this->getEntityManager()->find($class, $id);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if (null === $this->entityManager) {
            $serviceManager = $this->getServiceManager();
            $this->entityManager = $serviceManager->get($this->entityManagerAlias);
            $this->generateDatabase();
        }
        return $this->entityManager;
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->entityManager);
        unset($this->provider);
    }

    /**
     * @return string
     */
    abstract public function getFixturePath();
}