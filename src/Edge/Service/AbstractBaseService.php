<?php

namespace Edge\Service;

use Edge\EventManager\AbstractEventProvider;
use Zend\Form\Form;

abstract class AbstractBaseService extends AbstractEventProvider
{
    /**
     * @var Form
     */
    protected $form;

    /**
     * @var array
     */
    protected $errorMessages;


    /**
     * Set Form
     *
     * @param Form $form
     * @return self
     */
    public function setForm(Form $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        if (null === $this->form) {
            throw new RuntimeException('No form instance is available');
        }
        
        $this->getEventManager()->trigger(__FUNCTION__, $this, array('form' => $this->form));

        return $this->form;
    }

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