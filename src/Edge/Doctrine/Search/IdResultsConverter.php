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
     * @var int
     */
    protected $batchsize;

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string $searchfield search field for IN query [optional]
     * @param int    $batchsize   the number of records to retrieve from Doctrine in memory at once
     */
    public function __construct(QueryBuilder $qb, $searchfield = null, $batchsize = 100)
    {
        $this->qb = $qb;
        $this->batchsize = $batchsize;

        if (null !== $searchfield) {
            $this->searchfield = $searchfield;
        } else {
            $aliases = $qb->getRootAliases();
            $this->searchfield = reset($aliases) . '.id';
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
            return $data;
        }

        $this->qb->andWhere($this->qb->expr()->in($this->searchfield, $data));

        $batches = (int) ceil(count($data) / $this->batchsize);
        $return  = array();
        $batch   = 1;

        while ($batch <= $batches) {
            $results = $this->getBatch(($batch - 1) * $this->batchsize, $this->batchsize);
            foreach ($results as $entity) {
                $return[] = $entity->toArray();
            }
            $this->qb->getEntityManager()->clear();
            $batch++;
        }

        return $return;
    }

    public function getBatch($offset, $limit)
    {
        $this->qb->setFirstResult($offset);
        $this->qb->setMaxResults($limit);
        return $this->qb->getQuery()->getResult();
    }
}