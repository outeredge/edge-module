<?php

namespace Edge\EventManager;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

abstract class AbstractEventProvider implements EventManagerAwareInterface {

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Set the event manager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return AbstractEventProvider
     */
    public function setEventManager(EventManagerInterface $eventManager) {
        $eventManager->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
            'Zebreco',
        ));
        $this->events = $eventManager;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * @return EventManagerInterface
     */
    public function getEventManager() {
        return $this->events;
    }

}