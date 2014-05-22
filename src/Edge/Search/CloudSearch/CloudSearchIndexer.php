<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\Exception;
use Edge\Search\IndexerInterface;
use Edge\Search\IndexableEntityInterface;
use Zend\Http;
use Zend\Json\Json;

class CloudSearchIndexer implements IndexerInterface
{
    const METHOD_ADD    = 'add';
    const METHOD_DELETE = 'delete';

    protected $documentEndpoint;

    /**
     * Throw exceptions if indexing fails
     *
     * @var bool
     */
    protected $throwExceptions = false;

    protected $apiversion = '2011-02-01';

    public function __construct($endpoint)
    {
        $this->documentEndpoint = $endpoint;
    }

    public function add($entities)
    {
        return $this->updateIndex($entities, self::METHOD_ADD);
    }

    public function update($entities)
    {
        return $this->updateIndex($entities, self::METHOD_ADD);
    }

    public function delete($entities)
    {
        return $this->updateIndex($entities, self::METHOD_DELETE);
    }

    protected function updateIndex($entities, $method)
    {
        if (null === $this->documentEndpoint) {
            return true;
        }

        $data = array();

        if (!is_array($entities)) {
            $entities = array($entities);
        }

        foreach ($entities as $entity) {
            if ($entity instanceof IndexableEntityInterface) {
                $data[] = $this->prepareIndexData($entity->toSearchArray(), $method);
            } elseif (is_array($entity)) {
                $data[] = $this->prepareIndexData($entity, $method);
            } else {
                throw new Exception\RuntimeException('Invalid entity provided for indexing');
            }
        }

        $count = 0;
        foreach (array_chunk($data, 5000) as $chunk) {
            $result = $this->index($chunk);
            if (!$result) {
                $count = 0;
                break;
            }
            $count = $count + $result;
        }

        return $count;
    }

    protected function prepareIndexData(array $entity, $method)
    {
        $fields = array_filter($entity);

        if (!isset($fields['docid'])) {
            throw new Exception\RuntimeException('Missing array key docid is required');
        }

        $data = array(
            'type'    => $method,
            'id'      => $fields['docid'],
            'version' => time(),
        );

        if ($method == 'add') {
            $data['lang']   = 'en';
            $data['fields'] = $fields;
        }

        return $data;
    }

    /**
     * Store or update an entity in the search index
     *
     * @param  array $data
     * @throws Exception\RuntimeException
     * @throws Exception\IndexException
     * @return boolean true if successfull
     */
    protected function index(array $data)
    {
        $adapter = new Http\Client\Adapter\Curl();
        $adapter->setOptions(array(
            'curloptions' => array(
                CURLOPT_SSL_VERIFYPEER => false,
            )
        ));

        $client  = new Http\Client();
        $client->setAdapter($adapter);
        $client->setMethod('POST');
        $client->setUri($this->getDocumentEndpoint());
        $client->setRawBody(Json::encode($data));
        $client->setHeaders(array('Content-Type' => 'application/json'));

        $response = $client->send();

        if (!$response->isSuccess()) {
            if ($this->throwExceptions) {
                throw new Exception\IndexException("Bad response received from CloudSearch.\n" . $response->toString());
            }
            return false;
        }

        $results = Json::decode($response->getContent(), Json::TYPE_ARRAY);
        $count   = $results['adds'] + $results['deletes'];

        return $count != count($data) ? 0 : $count;
    }

    /**
     * Set whether to throw exceptions on failed indexing,
     * if false (default) the entity will have unindexed set on failure
     *
     * @param bool $throwExceptions
     * @return \Edge\Search\CloudSearch\CloudSearchIndexer
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;
        return $this;
    }

    protected function getDocumentEndpoint()
    {
        return sprintf(
            'https://%s/%s/documents/batch',
            $this->documentEndpoint,
            $this->apiversion
        );
    }
}