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
     * @param string $searchfield search field for IN query [optional]
     */
    public function __construct(QueryBuilder $qb, $searchfield = null)
    {
        $this->qb = $qb;

        if (null !== $searchfield) {
            $this->searchfield = $searchfield;
        } else {
            $aliases = $qb->getRootAliases();
            $this->searchfield = reset($aliases) . '.id';
        }
    }

    /**
     * Convert an array of ID's
     *
     * @param array $data
     */
    public function convert($data)
    {
        if (empty($data)) {
            return $data;
        }

        $qb = $this->qb;
        $qb->andWhere($qb->expr()->in($this->searchfield, $data));

        return $qb->getQuery()->getResult();
    }
}