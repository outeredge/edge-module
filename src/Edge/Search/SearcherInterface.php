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
     * @return mixed
     */
    public function getResults();

    /**
     * @return int
     */
    public function getCount();
}