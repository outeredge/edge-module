<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\Filter;
use Edge\Search\ConverterInterface;
use Edge\Search\SearcherInterface;

class CloudSearchSearcher implements SearcherInterface
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * @var bool
     */
    protected $returnIdResults = true;

    /**
     * @param \Edge\Search\Filter $filter
     * @param \Edge\Search\ConverterInterface $converter
     * @param boolean $returnIdResults whether to return an array of ID's or all results
     */
    public function __construct(Filter $filter, ConverterInterface $converter = null, $returnIdResults = true)
    {
        $this->filter = $filter;
        $this->converter = $converter;
        $this->returnIdResults = $returnIdResults;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        $results = array();

        foreach ($this->filter->getAllFieldValues() as $field => $values) {

        }

        if ($this->returnIdResults) {
            $results = $this->extractResultsToIdArray($results);
        }

        if (null !== $this->converter) {
            $results = $this->converter->convert($results);
        }

        return $results;
    }

    protected function extractResultsToIdArray(array $results)
    {
        return $results;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        die('not implemented');
    }
}