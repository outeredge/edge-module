<?php

namespace Edge\Search\CloudSearch;

use Zend\Stdlib\AbstractOptions;

class CloudSearchSearcherOptions extends AbstractOptions
{
    protected $searchEndpoint;

    protected $returnIdResults = true;

    protected $apiversion = '2011-02-01';

    protected $fieldMappings = array();
    

    public function setFieldMappings(array $fields)
    {
        $this->fieldMappings = $fields;
        return $this;
    }

    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

    /**
     * @param string $endpoint your CloudSearch search endpoint
     */
    public function setSearchEndpoint($endpoint)
    {
        $this->searchEndpoint = $endpoint;
        return $this;
    }

    public function getSearchEndpoint()
    {
        return sprintf(
            'https://%s/%s/search?',
            $this->searchEndpoint,
            $this->apiversion
        );
    }

    /**
     * @param boolean $returnIdResults whether to return an array of ID's or all results
     */
    public function setReturnIdResults($returnIdResults)
    {
        $this->returnIdResults = $returnIdResults;
        return $this;
    }

    public function getReturnIdResults()
    {
        return $this->returnIdResults;
    }

    /**
     * @param string $version change the default API version
     */
    public function setApiVersion($version)
    {
        $this->apiversion = $version;
        return $this;
    }

    public function getApiVersion()
    {
        return $this->apiversion;
    }
}