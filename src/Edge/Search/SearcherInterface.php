<?php

namespace Edge\Search;

use Traversable;

interface SearcherInterface
{
    /**
     * @param $offset
     * @param $itemCountPerPage
     * @return array|Traversable
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
     * @param int $priority
     */
    public function addConverter(ConverterInterface $converter, $priority = 1);

    /**
     * @return \SplPriorityQueue|ConverterInterface[]
     */
    public function getConverters();
}