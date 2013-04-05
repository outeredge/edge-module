<?php

namespace Edge\Rest\Mvc\Controller;

use Edge\Exception;
use Edge\Rest\ApiProblem;
use Edge\Service\Exception as ServiceException;
use Zend\Mvc\Controller\AbstractRestfulController as ZendRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;

abstract class AbstractRestfulController extends ZendRestfulController
{
    public function onDispatch(MvcEvent $e)
    {
        // Check for an API-Problem in the event, prevents futher dispatch
        $return = $e->getParam('api-problem', false);

        if (!$return) {
            try {
                $return = parent::onDispatch($e);
            } catch (ServiceException\ExceptionInterface $ex) {
                $return = $this->handleServiceException($ex);
                $e->setResult($return);
            }
        }

        return $return;
    }

    protected function handleServiceException(ServiceException\ExceptionInterface $ex)
    {
        if ($ex instanceof ServiceException\EntityErrorsException) {
            $code   = $ex->getCode() ?: 500;
            $return = $this->prepareProblemJsonModel(new ApiProblem($code, $ex->getMessage(), null, null, array('errors' => $ex->getErrors())));
        } elseif ($ex instanceof ServiceException\EntityNotFoundException) {
            $return = $this->prepareProblemJsonModel(new ApiProblem(404, 'Resource not found'));
        } else {
            throw $ex;
        }

        return $return;
    }

    /**
     * Create an api-problem JsonModel
     *
     * @param \Edge\Rest\ApiProblem $problem
     * @return \Zend\View\Model\JsonModel
     */
    protected function prepareProblemJsonModel(ApiProblem $problem)
    {
        $jsonModel = new JsonModel(array('api-problem' => $problem));
        return $jsonModel;
    }

    /**
     * Prepare a paginator from page and limit query strings
     *
     * @param \Zend\Paginator\Paginator $paginator
     * @return \Zend\Paginator\Paginator
     */
    public function preparePaginator(Paginator $paginator)
    {
        $page  = $this->params()->fromQuery('page', 1);
        $limit = $this->params()->fromQuery('limit', null);

        if (null === $limit) {
            $limit = 10;
        }

        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($limit);

        return $paginator;
    }

    /**
     * Bulk Process Data
     *
     * @param array  $data
     * @param string $entityName
     * @param string $entityIdentifier
     * @return JsonModel
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function processBulkData(array $data, $entityName, $entityIdentifier = 'id')
    {
        $data = reset($data);

        if (!is_array($data) || !ArrayUtils::isList($data)) {
            throw new Exception\InvalidArgumentException('Invalid data provided');
        }

        $responses = array();

        foreach ($data as $entityArray) {
            try {
                if ($this->getRequest()->getMethod() == 'POST') {
                    $result = $this->create(array($entityName => $entityArray));
                } else {
                    $id = isset($entityArray[$entityIdentifier]) ? $entityArray[$entityIdentifier] : null;
                    $result = $this->update($id, array($entityName => $entityArray));
                }
            } catch (ServiceException\ExceptionInterface $ex) {
                $result = $this->handleServiceException($ex);
            }

            if (!$result instanceof JsonModel) {
                throw new Exception\RuntimeException(sprintf(
                    'Expected to receive a JsonModel, received %s',
                    is_object($result) ? get_class($result) : gettype($result)
                ));
            }

            $errors  = false;
            $problem = $result->getVariable('api-problem');

            if ($problem instanceof ApiProblem) {
                $responses[] = array(
                    'code'    => $problem->httpStatus,
                    'headers' => array('content-type' => 'application/api-problem+json'),
                    'body'    => $problem->toArray()
                );
                $errors = true;
            } else {
                $responses[] = array(
                    'code'    => $this->getResponse()->getStatusCode(),
                    'headers' => $this->getResponse()->getHeaders()->toArray(),
                    'body'    => $result->getVariables()
                );
            }
        }

        if ($errors) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_207);
        } else {
            if ($this->getRequest()->getMethod() == 'POST') {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
            } else {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            }
        }

        return new JsonModel(array('responses' => $responses));
    }
}