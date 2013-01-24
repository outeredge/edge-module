<?php

namespace Edge\Service;

use Edge\EventManager\AbstractEventProvider;

abstract class AbstractBaseService extends AbstractEventProvider
{
    /**
     * @var array
     */
    protected $errorMessages;

    /**
     * Generic service return, receives and assigns error messages for later retrieval
     *
     * @param array $messages
     * @return boolean
     */
    protected function setErrorMessages(array $messages)
    {
        $this->errorMessages = $messages;
        return false;
    }

    /**
     * Get array of current error messages on the service
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }
}