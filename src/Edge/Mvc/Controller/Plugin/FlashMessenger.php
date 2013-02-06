<?php

namespace Edge\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\FlashMessenger as ZendFlashMessenger;

class FlashMessenger extends ZendFlashMessenger
{
    /**
     * Get messages from a specific namespace,
     * customised to include current messages
     *
     * @return array
     */
    public function getMessages()
    {
        $return = array();

        if ($this->hasMessages()) {
            $return = $this->messages[$this->getNamespace()]->toArray();
        }

        if ($this->hasCurrentMessages()) {
            $return = array_merge($return, $this->getCurrentMessages());
            $this->clearCurrentMessages();
        }

        return $return;
    }
}