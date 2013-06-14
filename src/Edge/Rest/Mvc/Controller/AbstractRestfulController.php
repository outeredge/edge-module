<?php

namespace Edge\Rest\Mvc\Controller;

use Edge\Exception;
use Edge\Service\Exception as ServiceException;
use PhlyRestfully\ApiProblem;
use Zend\Mvc\Controller\AbstractRestfulController as ZendRestfulController;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;

abstract class AbstractRestfulController extends ZendRestfulController
{
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
     * Bulk Process Data, handles POST, PUT & DELETE
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

        $errors    = false;
        $responses = array();

        foreach ($data as $entityArray) {
            try {
                switch ($this->getRequest()->getMethod()) {
                    case 'POST':
                        $result = $this->create(array($entityName => $entityArray));
                        break;
                    case 'PUT':
                        $id = isset($entityArray[$entityIdentifier]) ? $entityArray[$entityIdentifier] : null;
                        $result = $this->update($id, array($entityName => $entityArray));
                        break;
                    case 'DELETE':
                        $id = isset($entityArray[$entityIdentifier]) ? $entityArray[$entityIdentifier] : null;
                        $result = $this->delete($id);
                        break;
                    default:
                        throw new Exception\RuntimeException('Invalid HTTP method specificed for bulk action');
                        break;
                }
            } catch (ServiceException\ExceptionInterface $ex) {
                $httpStatus = $ex->getCode() ?: 500;
                $result = new JsonModel(array('api-problem' => new ApiProblem($httpStatus, $ex)));
            }

            if (!$result instanceof JsonModel && !$result instanceof Response) {
                throw new Exception\RuntimeException(sprintf(
                    'Expected to receive a JsonModel or Response object, received %s',
                    is_object($result) ? get_class($result) : gettype($result)
                ));
            }

            if ($result instanceof Response) {
                $responses[] = array(
                    'code'    => $result->getStatusCode(),
                    'headers' => $this->getResponse()->getHeaders()->toArray(),
                    'body'    => null
                );
                if (!$result->isSuccess()) {
                    $errors = true;
                }
                continue;
            }

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
            switch ($this->getRequest()->getMethod()) {
                case 'POST':
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
                    break;
                case 'PUT':
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
                    break;
                case 'DELETE':
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_204);
                    break;
            }
        }

        return new JsonModel(array('responses' => $responses));
    }

    /**
     * Creates a "405 Method Not Allowed" response detailing the available options
     *
     * @param  array $options
     * @return Response
     */
    protected function createMethodNotAllowedResponse(array $options)
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_405);
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Allow', implode(', ', $options));
        return $response;
    }
}