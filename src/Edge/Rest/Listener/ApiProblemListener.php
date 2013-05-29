<?php

namespace Edge\Rest\Listener;

use PhlyRestfully\ApiProblem;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\JsonModel;

/**
 * ApiProblemListener
 *
 * Provides a listener on the render event, at high priority.
 *
 * If the MvcEvent represents an error, then its view model and result are
 * replaced with a JsonModel containing an API-Problem
 */
class ApiProblemListener implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER, __CLASS__ . '::onRender', 1000);
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Listen to the render event
     *
     * @param MvcEvent $e
     */
    public static function onRender(MvcEvent $e)
    {
        if (!$e->isError()) {
            return;
        }

        $model = $e->getResult();
        if (!$model instanceof ModelInterface) {
            return;
        }

        $exception  = $model->getVariable('exception');
        if (!$exception instanceof \Exception) {
            return;
        }

        $headers = $e->getRequest()->getHeaders();
        if (!$headers->has('accept')) {
            return;
        }

        $accept  = $headers->get('Accept');
        if (($match = $accept->match('application/json')) == false) {
            return;
        }

        if ($match->getTypeString() != 'application/json') {
            return;
        }

        $httpStatus = $exception->getCode() ?: 500;

        // Create a new model with the API-Problem,
        // reset the result and view model in the event
        $jsonModel = new JsonModel(array('api-problem' => new ApiProblem($httpStatus, $exception)));
        $e->setResult($jsonModel);
        $e->setViewModel($jsonModel);
    }
}
