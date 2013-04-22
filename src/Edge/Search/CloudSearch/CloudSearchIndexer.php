<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\Exception;
use Edge\Search\IndexerInterface;
use Edge\Search\IndexableEntityInterface;
use Zend\Http;

class CloudSearchIndexer implements IndexerInterface
{
    const METHOD_ADD    = 'add';
    const METHOD_DELETE = 'delete';

    protected $documentEndpoint;

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
        $data = array();

        if (!is_array($entities)) {
            $entities = array($entities);
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof IndexableEntityInterface) {
                throw new Exception\RuntimeException('Invalid entity provided for indexing');
            }
            $data[] = $this->prepareIndexData($entity, $method);
        }

        $result = $this->index($data);

        foreach ($entities as $entity) {
            $entity->setUnindexed(!$result);
        }

        return $result;
    }

    protected function prepareIndexData(IndexableEntityInterface $entity, $method)
    {
        $fields = array_filter($entity->toSearchArray());

        if (!isset($fields['id'])) {
            throw new Exception\RuntimeException('Missing array key id is required');
        }

        $data = array(
            'type'    => $method,
            'id'      => $fields['id'],
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
        $client->setRawBody(json_encode($data));
        $client->setHeaders(array('Content-Type' => 'application/json'));

        $response = $client->send();

        if (!$response->isSuccess()) {
            return false;
        }

        $results = json_decode($response->getContent(), true);
        $count   = $results['adds'] + $results['deletes'];

        return $count != count($data) ? 0 : $count;
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