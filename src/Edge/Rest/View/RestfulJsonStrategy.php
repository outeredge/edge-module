<?php

namespace Edge\Rest\View;

use Zend\View\Strategy\JsonStrategy;
use Zend\View\ViewEvent;

/**
 * Extension of the JSON strategy to handle ApiProblem
 * and provide a relevant Content-Type header:
 *
 * - application/api-problem+json for a result that contains a Problem
 *   API-formatted response
 * - application/javascript for jsonp responses
 * - application/json for all other responses
 */
class RestfulJsonStrategy extends JsonStrategy
{
    public function __construct(RestfulJsonRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Inject the response
     *
     * Injects the response with the rendered content, and sets the content
     * type based on the detection that occurred during renderer selection.
     *
     * @param ViewEvent $e
     */
    public function injectResponse(ViewEvent $e)
    {
        parent::injectResponse($e);

        if ($this->renderer->isApiProblem()) {
            $e->getResponse()->getHeaders()->addHeaderLine('content-type', 'application/api-problem+json');
            $e->getResponse()->setStatusCode($this->renderer->getApiProblem()->status);
        }
    }
}
