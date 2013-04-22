<?php

namespace Edge\Doctrine\Listener;

use Doctrine\ORM\EntityManager;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;

class FlushEntities
{
    const ERROR_FLUSH = 'error-flush';

    public static $requiresFlush = false;


    public static function flush(EventInterface $e)
    {
        if ($e->getParam('immediate') === false) {
            self::$requiresFlush = true;
            return;
        }

        try {
            if ($e->getTarget() instanceof EntityManager) {
                $em = $e->getTarget();
                if (null === $e->getParam('entity')) {
                    $em->flush();
                    self::$requiresFlush = false;
                } else {
                    $em->flush($e->getParam('entity'));
                }
                return;
            }

            if (!self::$requiresFlush) {
                return;
            }

            if (!$e instanceof MvcEvent) {
                return;
            }

            $e->getApplication()->getServiceManager()->get('EntityManager')->flush();

            self::$requiresFlush = false;
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