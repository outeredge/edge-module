<?php

namespace Edge\Doctrine\Search;

use Doctrine\ORM\QueryBuilder;
use Edge\Search\ConverterInterface;

class IdToResultConverter implements ConverterInterface
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
     * @param string $searchfield search field for IN query, uses rootAlias.id by default [optional]
     */
    public function __construct(QueryBuilder $qb, $searchfield = null)
    {
        $this->qb = $qb;

        if (null !== $searchfield) {
            $this->searchfield = $searchfield;
        } else {
            $this->searchfield = $qb->getRootAlias() . '.id';
        }
    }

    /**
     * Convert an array of ID's to Doctrine entities / arrays
     *
     * @param  array $data
     * @return array
     */
    public function convert($data)
    {
        if (empty($data)) {
            return array();
        }

        $this->qb->andWhere($this->qb->expr()->in($this->searchfield, $data));

        return $this->qb->getQuery()->getResult();
    }
}