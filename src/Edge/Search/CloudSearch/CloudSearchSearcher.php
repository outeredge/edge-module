<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\Exception;
use Edge\Search\Filter;
use Edge\Search\ConverterInterface;
use Edge\Search\SearcherInterface;
use Zend\Http;

class CloudSearchSearcher implements SearcherInterface
{
    protected $searchEndpoint = 'search-zebreco-jcegbhkc3znoi3wupvykolqy6i.eu-west-1.cloudsearch.amazonaws.com';

    protected $apiversion = '2011-02-01';

    protected $count;

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
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return array
     */
    public function getResults($offset, $itemCountPerPage)
    {
        $query = $this->createSearchQuery($this->getFilter(), $offset, $itemCountPerPage);

        $adapter = new Http\Client\Adapter\Curl();
        $adapter->setOptions(array(
            'curloptions' => array(
                CURLOPT_SSL_VERIFYPEER => false,
            )
        ));

        $client  = new Http\Client();
        $client->setAdapter($adapter);
        $client->setMethod('GET');
        $client->setUri($this->getSearchEndpoint() . $query);

        $response = $client->send();

        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException('Invalid response received from CloudSearch');
        }

        $results = json_decode($response->getContent(), true);

        $this->count = $results['hits']['found'];

        if ($this->returnIdResults) {
            $results = $this->extractResultsToIdArray($results);
        }

        if (null !== $this->converter) {
            $results = $this->converter->convert($results);
            $this->count = count($results);
        }

        return $results;
    }

    /**
     * Create a urlencoded search string from a Filter
     *
     * @param  \Edge\Search\Filter $filter
     * @param  $offset
     * @param  $itemCountPerPage
     * @return string
     */
    public function createSearchQuery(Filter $filter, $offset = 0, $itemCountPerPage = 10)
    {
        $params = array();
        foreach ($filter->getAllFieldValues() as $values) {
            foreach ($values as $data) {
                    switch ($data['comparison']) {
                        case Filter::COMPARISON_EQUALS:
                        case Filter::COMPARISON_LIKE:
                            $params[] = $this->getEqualsExpr($data['field'], $data['value']);
                            break;
                        case Filter::COMPARISON_NOT_EQUALS:
                            $params[] = $this->getNotEqualsExpr($data['field'], $data['value']);
                            break;
    //                    case Filter::COMPARISON_GREATER:
    //                        break;
    //                    case Filter::COMPARISON_GREATER_OR_EQ:
    //                        break;
    //                    case Filter::COMPARISON_LESS:
    //                        break;
    //                    case Filter::COMPARISON_LESS_OR_EQ:
    //                        break;
                        default:
                            continue;
                            break;
                    }
            }
        }

        $query  = array();

        if (!empty($params)) {
            $query[] = "bq=(and " . implode(' ', $params) . ")";
        }

        if (null !== $filter->getKeywords()) {
            $query[] = 'q=' . addslashes($filter->getKeywords());
        }

        if (empty($query)) {
            // @todo amazon requires at least a q or bq
        }

        if (null !== $filter->getSortField()) {
            if ($filter->getSortOrder() == Filter::ORDER_DESC) {
                $query[] = 'rank=-' . $filter->getSortField();
            } else {
                $query[] = 'rank=' . $filter->getSortField();
            }
        }

        $query[] = 'size=' . $itemCountPerPage;
        $query[] = 'start=' . $offset;

        return implode('&', $query);
    }

    protected function getEqualsExpr($field, $value)
    {
        if (is_array($field)){
            $expr = array();
            foreach ($field as $fieldName) {
                $expr[] = sprintf("%s:'%s'", $fieldName, addslashes($value));
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }
        return sprintf("(field %s '%s')", $field, addslashes($value));
    }

    protected function getNotEqualsExpr($field, $value)
    {
        if (is_array($field)){
            $expr = array();
            foreach ($field as $fieldName) {
                $expr[] = sprintf("%s:'-%s'", $fieldName, addslashes($value));
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }
        return sprintf("(not %s:'%s')", $field, addslashes($value));
    }

    protected function extractResultsToIdArray(array $results)
    {
        $idResults = array();
        foreach ($results['hits']['hit'] as $result) {
            $idResults[] = $result['id'];
        }
        return $idResults;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getSearchEndpoint()
    {
        return sprintf(
            'https://%s/%s/search?',
            $this->searchEndpoint,
            $this->apiversion
        );
    }
}