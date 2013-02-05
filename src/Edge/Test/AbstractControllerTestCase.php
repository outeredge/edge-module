<?php

namespace Edge\Test;

use Zend\Console\Console;
use Zend\Http\PhpEnvironment\Request;
use Zend\Uri\Http as HttpUri;
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
        Console::overrideIsConsole(false);

        parent::setUp();

        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => $this->controllerName));
        $this->event      = $this->application->getMvcEvent();
        $this->event->setRequest($this->request);
        $this->event->setRouteMatch($this->routeMatch);
        $this->event->getRouter()->setRequestUri(new HttpUri('http://localhost'));

        if (null === $this->controller) {
            $this->controller = $this->getServiceManager()->get('ControllerLoader')->get($this->controllerName);
        }

        $this->controller->setEvent($this->event);
    }
}