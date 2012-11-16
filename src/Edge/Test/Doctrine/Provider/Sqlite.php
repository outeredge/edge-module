<?php

namespace Edge\Test\Doctrine\Provider;

use Doctrine\ORM\EntityManager;

class Sqlite implements ProviderInterface
{
    public function deleteDatabase($databaseName)
    {
        foreach (array($databaseName, $databaseName . '.bk') as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function backupDatabase($databaseName)
    {
        copy($databaseName, $databaseName . '.bk');
    }

    public function restoreDatabase($databaseName)
    {
        if (file_exists($databaseName . '.bk')) {
            copy($databaseName . '.bk', $databaseName);
            return true;
        }
        return false;
    }

    public function setEntityManager(EntityManager $em) {}

    public function getEntityManager() {}
}