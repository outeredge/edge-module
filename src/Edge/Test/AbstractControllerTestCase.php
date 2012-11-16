<?php

namespace Edge\Test;

use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

abstract class AbstractControllerTestCase extends AbstractMvcTestCase
{
    /**
     * Controller Name in SM to load for tests
     *
     * @var string
     */
    protected $controllerName;

    /**
     * @var \Zend\Mvc\Controller\AbstractController
     */
    protected $controller;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @var MvcEvent
     */
    protected $event;


    public function setUp()
    {
        parent::setUp();

        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => $this->controllerName));
        $this->event      = $this->application->getMvcEvent();
        $this->event->setRouteMatch($this->routeMatch);

        $this->controller = $this->serviceManager->get('ControllerLoader')->get($this->controllerName);
        $this->controller->setEvent($this->event);
    }
}