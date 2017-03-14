<?php

namespace Edge\Doctrine\Listener;

use Doctrine\ORM\EntityManager;
use Edge\Exception;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;

class FlushEntities
{
    public static function flush(EventInterface $e)
    {
        if ($e instanceof MvcEvent && $e->isError()) {
            return;
        }

        if ($e->getTarget() instanceof EntityManager) {
            $em = $e->getTarget();
        } elseif ($e instanceof MvcEvent) {
            $em = $e->getApplication()->getServiceManager()->get('EntityManager');
        } else {
            throw new Exception\RuntimeException('No EntityManager instance available');
        }

        if (null !== $e->getParam('entity')) {
            $em->flush($e->getParam('entity'));
        } else {
            $em->flush();
        }
    }
}
