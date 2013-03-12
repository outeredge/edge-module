<?php

namespace Edge\View\Helper;

use Zend\Mvc\Router\RouteMatch;
use Zend\View\Helper\AbstractHelper;

class Route extends AbstractHelper
{
    protected $routeMatch;

    public function __construct(RouteMatch $routeMatch) {
        $this->routeMatch = $routeMatch;
    }

    public function __invoke() {
        $controller = $this->routeMatch->getParam('controller');
        return $controller;
    }
}