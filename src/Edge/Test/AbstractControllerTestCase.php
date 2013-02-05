<?php

namespace Edge\Test;

use PHPUnit_Framework_ExpectationFailedException;
use PHPUnit_Framework_Exception;
use Zend\Console\Console;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Controller\AbstractController;
use Zend\Stdlib\ResponseInterface;
use Zend\Uri\Http as HttpUri;

abstract class AbstractControllerTestCase extends AbstractTestCase
{
    /**
     * Controller Name in SM to load for tests
     *
     * @var string
     */
    protected $controllerName;

    /**
     * @var AbstractController
     */
    private $controller;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * @var MvcEvent
     */
    private $event;


    public function setUp()
    {
        Console::overrideIsConsole(false);

        parent::setUp();

        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => $this->controllerName));

        $this->event = $this->getApplication()->getMvcEvent();
        $this->event->setRequest($this->request);
        $this->event->setRouteMatch($this->routeMatch);
        $this->event->getRouter()->setRequestUri(new HttpUri('http://localhost'));

        if (null === $this->controller) {
            if (null === $this->controllerName) {
                throw new PHPUnit_Framework_Exception('No controller name was specified in the test');
            }
            $this->controller = $this->getServiceManager()->get('ControllerLoader')->get($this->controllerName);
        }

        $this->controller->setEvent($this->event);
    }

    protected function setController(AbstractController $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get the controller
     *
     * @return AbstractController
     */
    protected function getController()
    {
        return $this->controller;
    }

    /**
     * Dispatch the request
     *
     * @return ResponseInterface|mixed
     */
    protected function dispatch($method = Request::METHOD_GET, $params = array(), $headers = array())
    {
        foreach ($params as $name => $param) {
            $this->getRouteMatch()->setParam($name, $param);
        }

        if (!empty($headers)) {
            $this->getRequest()->getHeaders()->addHeaders($headers);
        }

        $this->getRequest()->setMethod($method);

        return $this->getController()->dispatch($this->getRequest());
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the controller response object
     *
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        return $this->getController()->getResponse();
    }

    /**
     * @return RouteMatch
     */
    protected function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * @return MvcEvent
     */
    protected function getEvent()
    {
        return $this->event;
    }

    /**
     * Get response header by key
     *
     * @param string $header
     * @return \Zend\Http\Header\HeaderInterface|false
     */
    protected function getResponseHeader($header)
    {
        return $this->getResponse()->getHeaders()->get($header, false);
    }

    /**
     * Assert response status code
     *
     * @param int $code
     */
    public function assertResponseStatusCode($code)
    {
        $match = $this->getResponse()->getStatusCode();
        if ($code != $match) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response code "%s", actual status code is "%s"',
                $code, $match
            ));
        }
        $this->assertEquals($code, $match);
    }

    /**
     * Assert response header exists
     *
     * @param string $header
     */
    public function assertHasResponseHeader($header)
    {
        $responseHeader = $this->getResponseHeader($header);
        if (false === $responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response header "%s" found', $header
            ));
        }
        $this->assertNotEquals(false, $responseHeader);
    }

    /**
     * Assert response header does not exist
     *
     * @param string $header
     */
    public function assertNotHasResponseHeader($header)
    {
        $responseHeader = $this->getResponseHeader($header);
        if (false !== $responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response header "%s" WAS NOT found', $header
            ));
        }
        $this->assertFalse($responseHeader);
    }

    /**
     * Assert response header exists and contains the given string
     *
     * @param string $header
     * @param string $match
     */
    public function assertResponseHeaderContains($header, $match)
    {
        $responseHeader = $this->getResponseHeader($header);
        if (!$responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response header, header "%s" do not exists', $header
            ));
        }
        if (!stristr($responseHeader->getFieldValue(), $match)) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response header "%s" exists and contains "%s", actual content is "%s"',
                $header, $match, $responseHeader->getFieldValue()
            ));
        }
        $this->assertContains($match, $responseHeader->getFieldValue(), null, true);
    }

    /**
     * Assert that response is a redirect
     */
    public function assertRedirect()
    {
        $responseHeader = $this->getResponseHeader('Location');
        if (false === $responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(
                'Failed asserting response is NOT a redirect'
            );
        }
        $this->assertNotEquals(false, $responseHeader);
    }

    /**
     * Assert that response is NOT a redirect
     */
    public function assertNotRedirect()
    {
        $responseHeader = $this->getResponseHeader('Location');
        if (false !== $responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response is NOT a redirect, actual redirection is "%s"',
                $responseHeader->getFieldValue()
            ));
        }
        $this->assertFalse($responseHeader);
    }

    /**
     * Assert that response redirects to given URL
     *
     * @param string $url
     */
    public function assertRedirectTo($url)
    {
        $responseHeader = $this->getResponseHeader('Location');
        if (!$responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(
                'Failed asserting response is a redirect'
            );
        }
        if ($url != $responseHeader->getFieldValue()) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response redirects to "%s", actual redirection is "%s"',
                $url, $responseHeader->getFieldValue()
            ));
        }
        $this->assertEquals($url, $responseHeader->getFieldValue());
    }

    /**
     * Assert that the redirect header contains specified string
     *
     * @param string $match
     */
    public function assertRedirectContains($match)
    {
        $responseHeader = $this->getResponseHeader('Location');
        if (!$responseHeader) {
            throw new PHPUnit_Framework_ExpectationFailedException(
                'Failed asserting response is a redirect'
            );
        }
        if (!stristr($responseHeader->getFieldValue(), $match)) {
            throw new PHPUnit_Framework_ExpectationFailedException(sprintf(
                'Failed asserting response redirect contains "%s", actual redirection is "%s"',
                $match, $responseHeader->getFieldValue()
            ));

        }
        $this->assertContains($match, $responseHeader->getFieldValue(), null, true);
    }

    /**
     * Assert that result is a JsonModel
     */
    public function assertJsonModel($result)
    {
        $this->assertInstanceOf('Zend\View\Model\JsonModel', $result);
    }

    /**
     * Assert that result is a ViewModel
     */
    public function assertViewModel($result)
    {
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
    }
}