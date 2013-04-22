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

    public function setFilter(Filter $filter);

    public function getFilter();

    public function setConverter(ConverterInterface $converter);

    public function getConverter();
}