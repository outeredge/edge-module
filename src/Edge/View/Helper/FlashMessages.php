<?php

namespace Edge\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FlashMessages extends AbstractHelper {

    protected $friendlyTitles = array(
        'warning' => '<strong>Warning!</strong> ',
        'error' => '<strong>Oh snap!</strong> ',
        'success' => '<strong>Well done!</strong> ',
        'info' => '<strong>Heads up!</strong> '
    );
    protected $defaultLevel = 'warning';

    /**
     * @var \Zend\Mvc\Controller\Plugin\FlashMessenger
     */
    protected $flashMessenger;

    /**
     * Display flashMessages
     *
     * @param string $defaultLevel the default level to used when none is set on a message
     * @param string $template template html used for displaying messages, default is Bootstrap2 style
     * @param string $namespace optional FlashMessenger namespace to retrieve messages from
     * @return string
     */
    public function __invoke(
    $defaultLevel = 'error', $template = '<div class="alert alert-%s"><button class="close" data-dismiss="alert">×</button>%s</div>', $namespace = 'default') {

        $flashMessenger = $this->getFlashMessenger();
        $flashMessenger->setNamespace($namespace);

        $messages = $flashMessenger->getMessages();

        if ($flashMessenger->hasCurrentMessages()) {
            $messages = array_merge(
                    $messages, $flashMessenger->getCurrentMessages()
            );
            $flashMessenger->clearCurrentMessages();
        }

        $output = '';

        if (!count($messages)) {
            return $output;
        }

        foreach ($messages as $message) {
            if (is_array($message)) {
                list($defaultLevel, $message) = each($message);
            }
            if (isset($this->friendlyTitles[$defaultLevel])) {
                $message = $this->friendlyTitles[$defaultLevel] . $message;
            }
            $output .= sprintf($template, $defaultLevel, $message);
        }

        return $output;
    }

    /**
     * Get FlashMessenger Controller Plugin
     *
     * @return \Zend\Mvc\Controller\Plugin\FlashMessenger
     */
    protected function getFlashMessenger() {
        return $this->flashMessenger;
    }

    public function setFlashMessenger($flashMessenger) {
        $this->flashMessenger = $flashMessenger;
        return $this;
    }

}