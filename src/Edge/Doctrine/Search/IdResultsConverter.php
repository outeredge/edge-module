<?php

namespace Edge\Doctrine\Search;

use Doctrine\ORM\QueryBuilder;
use Edge\Search\ConverterInterface;

class IdResultsConverter implements ConverterInterface
{
    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $qb;

    /**
     * @var string
     */
    protected $searchfield;

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string $searchfield search field for IN query
     */
    public function __construct(QueryBuilder $qb, $searchfield)
    {
        $this->qb = $qb;
        $this->searchfield = $searchfield;
    }

    /**
     * Convert an array of ID's
     *
     * @param array $data
     */
    public function convert($data)
    {
        $qb = $this->qb;
        $qb->andWhere($qb->expr()->in($this->searchfield, $data));
        return $qb->getQuery()->getResult();
    }
}