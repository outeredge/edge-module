<?php

namespace Edge\Mvc\Controller;

use Zend\Json\Json;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Mvc\Controller\AbstractRestfulController as RestfulController;

abstract class AbstractRestfulController extends RestfulController {

    public function processPostData(Request $request)
    {
        return $this->create($this->processInput($request, array('post')));
    }

    public function processPutData(Request $request, $routeMatch)
    {
        if (null === $id = $routeMatch->getParam('id')) {
            throw new \DomainException('Missing identifier');
        }

        return $this->update($id, $this->processInput($request, array('put')));
    }

    /**
     * Process request input data
     *
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @param array $validMethods list of valid HTTP methods
     * @param array $validTypes list of valid content-type headers
     * @return mixed
     * @throws \Exception
     */
    public function processInput(
        Request $request,
        array $validMethods,
        array $validTypes = array('application/json')
    ) {
        if (!in_array(strtolower($this->getRequest()->getMethod()), $validMethods)) {
            throw new \Exception('Invalid HTTP method!');
        }

        $valid = false;
        foreach ($validTypes as $type) {
            if (strstr($request->getHeaders()->get('Content-Type')->getFieldValue(), $type)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new \Exception('Invalid Content-Type header specified');
        }

        if (strstr($request->getHeaders()->get('Content-Type')->getFieldValue(), 'application/json')) {
            $return = Json::decode($request->getContent(), Json::TYPE_ARRAY);
            if(!count($return)) {
                throw new \Exception('No content provided');
            }
        } else {
            $return = $request->getContent();
        }

        return $return;
    }

}