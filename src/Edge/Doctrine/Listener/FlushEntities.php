<?php

namespace Edge\Doctrine\Listener;

use Doctrine\ORM\EntityManager;
use Edge\Exception;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;

class FlushEntities
{
    const ERROR_FLUSH = 'error-flush';

    public static function flush(EventInterface $e)
    {
        try {
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
        } catch (\Exception $ex) {
            if (!$e instanceof MvcEvent) {
                throw $ex;
            }

            $e->setError(self::ERROR_FLUSH)
              ->setParam('exception', $ex);

            $e->getApplication()->getEventManager()->trigger(MvcEvent::EVENT_DISPATCH_ERROR, $e);
        }
    }
}