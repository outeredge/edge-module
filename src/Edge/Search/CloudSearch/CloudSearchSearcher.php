<?php

namespace Edge\Search\CloudSearch;

use Edge\Search\AbstractSearcher;
use Edge\Search\Exception;
use Edge\Search\Filter;
use Zend\Http;

class CloudSearchSearcher extends AbstractSearcher
{
    /**
     * @var CloudSearchSearcherOptions
     */
    protected $options;

    /**
     * Set options
     *
     * @param CloudSearchSearcherOptions|array $options
     */
    public function setOptions($options)
    {
        if (!$options instanceof CloudSearchSearcherOptions) {
            $options = new CloudSearchSearcherOptions($options);
        }
        $this->options = $options;
    }

    /**
     * @return CloudSearchSearcherOptions
     * @throws RuntimeException
     */
    public function getOptions()
    {
        if (null === $this->options) {
            throw new Exception\RuntimeException('No options were specified');
        }
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getResults($offset, $itemCountPerPage)
    {
        $query = $this->createSearchQuery($offset, $itemCountPerPage);

        $adapter = new Http\Client\Adapter\Curl();
        $adapter->setOptions(array(
            'curloptions' => array(
                CURLOPT_SSL_VERIFYPEER => false,
            )
        ));

        $client  = new Http\Client();
        $client->setAdapter($adapter);
        $client->setMethod('GET');
        $client->setUri($this->getOptions()->getSearchEndpoint() . $query);

        $response = $client->send();

        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException("Invalid response received from CloudSearch.\n" . $response->getContent());
        }

        $results = json_decode($response->getContent(), true);

        $this->count = $results['hits']['found'];

        if ($this->count < 1) {
            return array();
        }

        if ($this->getOptions()->getReturnIdResults()) {
            $results = $this->extractResultsToIdArray($results);
        }

        if (null !== $this->getConverter()) {
            $results = $this->getConverter()->convert($results);
        }

        return $results;
    }

    /**
     * Create a urlencoded search string from a Filter
     *
     * @param  $offset
     * @param  $itemCountPerPage
     * @return string
     */
    public function createSearchQuery($offset = 0, $itemCountPerPage = 10)
    {
        $filter = $this->getFilter();
        $params = array();

        foreach ($filter->getAllFieldValues() as $field => $values) {
            foreach ($values as $data) {
                if (empty($data['value'])) {
                    $data['value'] = 0;
                }

                switch ($data['comparison']) {
                    case Filter::COMPARISON_EQUALS:
                    case Filter::COMPARISON_LIKE:
                        $params[] = $this->getEqualsExpr($field, $data['value']);
                        break;
                    case Filter::COMPARISON_NOT_EQUALS:
                        $params[] = $this->getNotEqualsExpr($field, $data['value']);
                        break;
                    case Filter::COMPARISON_GREATER:
                        // @todo in here we could possibly add a not for the value and a greater than or equal to for the value also?
                    case Filter::COMPARISON_GREATER_OR_EQ:
                        $params[] = $this->getGreaterThanOrEqualToExpr($field, $data['value']);
                        break;
                    case Filter::COMPARISON_LESS:
                    case Filter::COMPARISON_LESS_OR_EQ:
                        $params[] = $this->getLessThanOrEqualToExpr($field, $data['value']);
                        break;
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

    protected function getEqualsExpr($search, $value)
    {
        $field = $this->getMappedField($search);

        if (is_array($field['field'])){
            $expr = array();
            foreach ($field['field'] as $fieldName) {
                if ($field['numeric']) {
                    $expr[] = sprintf("%s:%s", $fieldName, $value);
                } else {
                    $expr[] = sprintf("%s:'%s'", $fieldName, addslashes($value));
                }
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }

        if ($field['numeric']) {
            return sprintf("(and %s:%s)", $field['field'], addslashes($value));
        }

        return sprintf("(field %s '%s')", $field['field'], addslashes($value));
    }

    protected function getNotEqualsExpr($search, $value)
    {
        $field = $this->getMappedField($search);

        if (is_array($field['field'])){
            $expr = array();
            foreach ($field['field'] as $fieldName) {
                if ($field['numeric']) {
                    $expr[] = sprintf("%s:-%s", $fieldName, $value);
                } else {
                    $expr[] = sprintf("%s:'-%s'", $fieldName, addslashes($value));
                }
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }

        if ($field['numeric']) {
            return sprintf("(not %s:%s)", $field['field'], $value);
        }

        return sprintf("(not %s:'%s')", $field['field'], addslashes($value));
    }

    protected function getGreaterThanOrEqualToExpr($search, $value)
    {
        $field = $this->getMappedField($search);

        if (!$field['numeric']) {
            return;
        }

        if (is_array($field['field'])){
            $expr = array();
            foreach ($field['field'] as $fieldName) {
                $expr[] = sprintf("%s:%s..", $fieldName, $value);
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }
        return sprintf("%s:%s..", $field['field'], $value);
    }

    protected function getLessThanOrEqualToExpr($search, $value)
    {
        $field = $this->getMappedField($search);

        if (!$field['numeric']) {
            return;
        }

        if (is_array($field['field'])){
            $expr = array();
            foreach ($field['field'] as $fieldName) {
                $expr[] = sprintf("%s:..%s", $fieldName, $value);
            }
            return sprintf("(or %s)", implode(' ', $expr));
        }
        return sprintf("%s:..%s", $field['field'], $value);
    }

    protected function extractResultsToIdArray(array $results)
    {
        $idResults = array();
        foreach ($results['hits']['hit'] as $result) {
            $idResults[] = $result['id'];
        }
        return $idResults;
    }

    public function getMappedField($name)
    {
        $mappedFields = $this->getOptions()->getFieldMappings();

        if (!isset($mappedFields[$name])) {
            throw new Exception\InvalidArgumentException("Invalid field [$name] specified");
        }

        $field = $mappedFields[$name];

        if (is_array($field) && isset($field['field'])) {
            return isset($field['numeric']) ? $field : array('field' => $field['field'], 'numeric' => false);
        }

        return array('field' => $field, 'numeric' => false);
    }
}