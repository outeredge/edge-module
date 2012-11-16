<?php

namespace Edge\Test\Doctrine\Provider;

use Doctrine\ORM\EntityManager;

interface ProviderInterface
{
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager();

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setEntityManager(EntityManager $em);

    /**
     * @param string $databaseName
     */
    public function deleteDatabase($databaseName);

    /**
     * @param string $databaseName
     */
    public function backupDatabase($databaseName);

    /**
     * @param string $databaseName
     * @return bool
     */
    public function restoreDatabase($databaseName);

}