<?php

namespace Edge\Search;

use Zend\Stdlib\PriorityQueue;
use Zend\Filter\FilterInterface;

abstract class AbstractSearcher implements SearcherInterface
{
    /**
     * @var int
     */
    protected $count;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var PriorityQueue
     */
    protected $converters;

    /**
     * @var array|FilterInterface[]
     */
    protected $valuefilters = [];


    public function __construct()
    {
        $this->converters = new PriorityQueue();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set the filter for searches
     *
     * @param Filter $filter
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get the applied filter
     *
     * @return Filter
     */
    public function getFilter()
    {
        if (null === $this->filter) {
            $this->filter = new Filter();
        }
        return $this->filter;
    }

    /**
     * Add a converter
     *
     * @param ConverterInterface $converter
     * @param int $priority
     */
    public function addConverter(ConverterInterface $converter, $priority = 1)
    {
        $this->converters->insert($converter, $priority);
        return $this;
    }

    /**
     * Get the converters
     *
     * @return PriorityQueue|ConverterInterface[]
     */
    public function getConverters()
    {
        return $this->converters;
    }

    public function addValueFilter($field, FilterInterface $filter)
    {
        $this->valuefilters[$field][] = $filter;
        return $this;
    }

    public function hasValueFilters($field)
    {
        return array_key_exists($field, $this->valuefilters)
               || array_key_exists('*', $this->valuefilters);
    }

    /**
     * Get value filter for field
     *
     * @param  string $field
     * @return array|FilterInterface[]
     * @throws Exception\InvalidArgumentException
     */
    public function getValueFilters($field)
    {
        if (isset($this->valuefilters[$field])) {
            return $this->valuefilters[$field];
        }

        if (!isset($this->valuefilters['*'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: no value filter by name of "%s", and no wildcard strategy present',
                __METHOD__,
                $field
            ));
        }

        return $this->valuefilters['*'];
    }

    public function removeValueFilters($field)
    {
        unset($this->valuefilters[$field]);
        return $this;
    }

    protected function prepareValue($value, $field)
    {
        if ($this->hasValueFilters($field)) {
            foreach ($this->getValueFilters($field) as $filter) {
                $value = $filter->filter($value);
            }
        }

        $mappedField = $this->getMappedField($field);
        $value = $this->handleTypeConversions($value, $mappedField['type']);

        return $value;
    }

    /**
     * Get mapped field
     *
     * @param  string $name
     * @return array
     * @throws Exception\InvalidArgumentException
     */
    public function getMappedField($name)
    {
        $mappedFields = $this->getOptions()->getFieldMappings();

        if (!isset($mappedFields[$name])) {
            throw new Exception\InvalidArgumentException("Invalid field [$name] specified");
        }

        $field = $mappedFields[$name];

        if (is_array($field) && isset($field['field'])) {
            return isset($field['type']) ? $field : array('field' => $field['field'], 'type' => null);
        }

        return array('field' => $field, 'type' => null);
    }

    /**
     * Handle conversions of strings to relevant field types
     *
     * @param  string  $value
     * @param  string  $type
     * @return mixed
     */
    abstract protected function handleTypeConversions($value, $type);
}
