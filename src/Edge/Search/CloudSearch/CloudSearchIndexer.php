<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\Exception;
use Edge\Search\IndexerInterface;
use Edge\Search\IndexableEntityInterface;
use Zend\Http;

class CloudSearchIndexer implements IndexerInterface
{
    protected $documentEndpoint = 'doc-zebreco-jcegbhkc3znoi3wupvykolqy6i.eu-west-1.cloudsearch.amazonaws.com';

    protected $searchEndpoint = 'search-zebreco-jcegbhkc3znoi3wupvykolqy6i.eu-west-1.cloudsearch.amazonaws.com';

    protected $apiversion = '2011-02-01';

    public function add(IndexableEntityInterface $entity)
    {
        $this->index($entity, 'add');
    }

    public function update(IndexableEntityInterface $entity)
    {
        $this->index($entity, 'add');
    }

    public function delete(IndexableEntityInterface $entity)
    {
        $this->index($entity, 'delete');
    }

    /**
     * Store or update an entity in the search index
     *
     * @param IndexableEntityInterface $entity
     * @param string $method add|delete
     * @throws Exception\RuntimeException
     * @throws Exception\IndexException
     */
    protected function index(IndexableEntityInterface $entity, $method = 'add')
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

        $adapter = new Http\Client\Adapter\Curl();
        $client  = new Http\Client();

        $client->setAdapter($adapter);
        $client->setMethod('POST');
        $client->setUri($this->getDocumentEndpoint());
        $client->setRawBody(json_encode(array($data)));
        $client->setHeaders(array('Content-Type' => 'application/json'));

        $response = $client->send();

        if (!$response->isSuccess()) {
            $entity->setIndexed(false);
            return;
        }

        $entity->setIndexed(true);
    }

    protected function getDocumentEndpoint()
    {
        return sprintf(
            'https://%s/%s/documents/batch',
            $this->documentEndpoint,
            $this->apiversion
        );
    }

    public function getSearchEndpoint()
    {
        return sprintf(
            'https://%s/%s/search',
            $this->searchEndpoint,
            $this->apiversion
        );
    }
}