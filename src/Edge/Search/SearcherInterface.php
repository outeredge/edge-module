<?php

namespace Edge\Search;

interface SearcherInterface
{
    /**
     * @param \Edge\Search\Filter $filter
     * @param \Edge\Search\ConverterInterface $converter
     */
    public function __construct(Filter $filter, ConverterInterface $converter = null);

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
}