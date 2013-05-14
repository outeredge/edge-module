<?php

namespace Edge\Search;

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
     * @var ConverterInterface
     */
    protected $converter;

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
     * Set the converter
     *
     * @param ConverterInterface|null $converter
     */
    public function setConverter(ConverterInterface $converter = null)
    {
        $this->converter = $converter;
        return $this;
    }

    /**
     * Get the converter
     *
     * @return ConverterInterface|null
     */
    public function getConverter()
    {
        return $this->converter;
    }
}