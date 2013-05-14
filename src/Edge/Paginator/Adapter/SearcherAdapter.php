<?php

namespace Edge\Paginator\Adapter;

use Edge\Search\SearcherInterface;
use Zend\Paginator\Adapter\AdapterInterface;

class SearcherAdapter implements AdapterInterface
{
    /**
     * @var SearcherInterface
     */
    protected $searcher;

    public function __construct(SearcherInterface $searcher)
    {
        $this->searcher = $searcher;
    }

    public function getSearcher()
    {
        return $this->searcher;
    }

    /**
     * {@inheritDoc}
     */
    public function getItems($offset, $itemCountPerPage)
    {
        return $this->searcher->getResults($offset, $itemCountPerPage);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->searcher->getCount();
    }
}