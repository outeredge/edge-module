<?php

namespace Edge\Rest\View;

use PhlyRestfully\ApiProblem;;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Model\JsonModel;

class RestfulJsonRenderer extends JsonRenderer
{
    /**
     * @var ApiProblem
     */
    protected $apiProblem;

    /**
     * Render exception stack traces in API-Problem payloads
     *
     * @var bool
     */
    protected $displayExceptions = false;

    /**
     * Set display_exceptions flag
     *
     * @param  bool $flag
     * @return RestfulJsonRenderer
     */
    public function setDisplayExceptions($flag)
    {
        $this->displayExceptions = (bool) $flag;
        return $this;
    }

    /**
     * Whether or not what was rendered represents an API problem
     *
     * @return bool
     */
    public function isApiProblem()
    {
        return (null !== $this->apiProblem);
    }

    /**
     * @return null|ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }

    /**
     * Render a view model
     *
     * If the view model has a variable 'api-problem' that is an ApiProblem,
     * return a customised representation.
     *
     * If not, it passes control to the parent.
     *
     * @param  mixed $nameOrModel
     * @param  mixed $values
     * @return string
     */
    public function render($nameOrModel, $values = null)
    {
        if (!$nameOrModel instanceof JsonModel) {
            return parent::render($nameOrModel, $values);
        }

        $problem = $nameOrModel->getVariable('api-problem');

        if (!$problem instanceof ApiProblem) {
            return parent::render($nameOrModel, $values);
        }

        return $this->renderApiProblem($problem);
    }

    /**
     * Render an API Problem representation
     *
     * Also sets the $apiProblem member to the passed object.
     *
     * @param  ApiProblem $apiProblem
     * @return string
     */
    protected function renderApiProblem(ApiProblem $apiProblem)
    {
        $this->apiProblem = $apiProblem;
        if ($this->displayExceptions) {
            $apiProblem->setDetailIncludesStackTrace(true);
        }
        return parent::render($apiProblem->toArray());
    }
}
