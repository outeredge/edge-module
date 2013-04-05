<?php

namespace Edge\Rest\Mvc\Controller;

use Edge\Exception;
use Edge\Rest\ApiProblem;
use Zend\Mvc\Controller\AbstractRestfulController as ZendRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\View\Model\JsonModel;

abstract class AbstractRestfulController extends ZendRestfulController
{
    public function onDispatch(MvcEvent $e)
    {
        // Check for an API-Problem in the event, prevents futher dispatch
        $return = $e->getParam('api-problem', false);

        if (!$return) {
            $return = parent::onDispatch($e);
        }

        if (!$return instanceof ApiProblem) {
            return $return;
        }

        $viewModel = new JsonModel(array('problem' => $return));
        $e->setResult($viewModel);
        return $viewModel;
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
            if ($this->getRequest()->getMethod() == 'POST') {
                $result = $this->create(array($entityName => $entityArray));
            } else {
                $id = isset($entityArray[$entityIdentifier]) ? $entityArray[$entityIdentifier] : null;
                $result = $this->update($id, array($entityName => $entityArray));
            }

            if (!$result instanceof JsonModel) {
                throw new Exception\RuntimeException(sprintf(
                    'Expected to receive a JsonModel, received %s',
                    is_object($result) ? get_class($result) : gettype($result)
                ));
            }

            $responses[] = array(
                'code'    => $this->getResponse()->getStatusCode(),
                'headers' => $this->getResponse()->getHeaders()->toArray(),
                'body'    => $result->getVariables()
            );
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_207);

        return new JsonModel(array('responses' => $responses));
    }
}