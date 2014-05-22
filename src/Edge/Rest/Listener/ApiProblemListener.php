<?php

namespace Edge\Rest\Listener;

use Exception;
use ZF\ApiProblem\ApiProblem;
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
     * Default values to match in Accept header
     *
     * @var string
     */
    protected static $acceptFilter = 'application/hal+json,application/api-problem+json,application/json';

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Constructor
     *
     * Set the accept filter, if one is passed
     *
     * @param string $filter
     */
    public function __construct($filter = null)
    {
        if (is_string($filter) && !empty($filter)) {
            self::$acceptFilter = $filter;
        }
    }

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

        if (self::$acceptFilter != '*') {
            $headers = $e->getRequest()->getHeaders();
            if (!$headers->has('Accept')) {
                return;
            }

            $match = $headers->get('Accept')->match(self::$acceptFilter);
            if (!$match || $match->getTypeString() == '*/*') {
                return;
            }
        }

        $model = $e->getResult();
        if (!$model instanceof ModelInterface) {
            return;
        }

        $exception  = $model->getVariable('exception');
        if (!$exception instanceof Exception) {
            return;
        }

        $jsonModel  = new JsonModel(array('api-problem' => new ApiProblem($exception->getCode(), $exception)));

        $e->setResult($jsonModel);
        $e->setViewModel($jsonModel);
    }
}
