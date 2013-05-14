<?php

namespace Edge\Search;

interface SearcherInterface
{
    /**
     * @param $offset
     * @param $itemCountPerPage
     * @return mixed
     */
    public function getResults($offset, $itemCountPerPage);

    /**
     * @return int
     */
    public function getCount();

    /**
     * @param Filter $filter
     */
    public function setFilter(Filter $filter);

    /**
     * @return Filter
     */
    public function getFilter();

    /**
     * @param ConverterInterface $converter
     */
    public function setConverter(ConverterInterface $converter);

    /**
     * @return ConverterInterface
     */
    public function getConverter();
}