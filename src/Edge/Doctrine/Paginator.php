<?php

namespace Edge\Doctrine;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrinePaginatorAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\ORM\QueryBuilder;
use Edge\Paginator\Paginator as EdgePaginator;

class Paginator extends EdgePaginator
{
    /**
     * Create a Zend Paginator Object from a QueryBuilder
     *
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $adapter = new DoctrinePaginatorAdapter(new DoctrinePaginator($qb));
        parent::__construct($adapter);
    }
}