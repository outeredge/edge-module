<?php

namespace Edge\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Edge\Search\Filter;

class AbstractRepository extends EntityRepository
{
    /**
     * Allowed search fields
     * @var array
     */
    protected $validSearchFields = array();

    /**
     * Fields to apply a %like% search to
     * @var array
     */
    protected $keywordSearchFields = array();

    /**
     * Array of field names and their default values
     * @var array
     */
    protected $defaultValues = array();

    /**
     * Add filter to QueryBuilder
     *
     * @param Filter $filter
     * @param Doctrine\ORM\QueryBuilder $qb
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getFilteredQueryBuilder(Filter $filter, QueryBuilder &$qb)
    {
        $orXs  = array();
        $i = 1;

        foreach ($filter->getAllFieldValues() as $field => $data) {
            $i++;
            $value = $data['value'];

            if (!isset($orXs[$field])) {
                $orXs[$field] = $qb->expr()->orX();
            }

            if ($data['equals']) {
                if (null === $value) {
                    $orXs[$field]->add($filter->validSearchFields[$field] . ' IS NULL');
                } else {
                    if (is_array($value)) {
                        $orXs[$field]->add($qb->expr()->in($this->validSearchFields[$field], ':'.$field.$i));
                    } else {
                        $orXs[$field]->add($qb->expr()->eq($this->validSearchFields[$field], ':'.$field.$i));
                    }
                    $qb->setParameter($field . $i, $value);
                }
            } else {
                if (null === $value) {
                    $orXs[$field]->add('NOT '. $this->validSearchFields[$field] . ' IS NULL');
                } else {
                    if (is_array($value)) {
                        $orXs[$field]->add($qb->expr()->notIn($this->validSearchFields[$field], ':'.$field.$i));
                    } else {
                        $orXs[$field]->add($qb->expr()->neq($this->validSearchFields[$field], ':'.$field.$i));
                    }
                    $qb->setParameter($field . $i, $value);
                }
            }
        }

        if (count($orXs)) {
            foreach ($orXs as $where) {
                $qb->andWhere($where);
            }
        }

        if (null !== $filter->getKeywords() && count($this->keywordSearchFields)) {
            $orX = $qb->expr()->orX();
            foreach ($this->keywordSearchFields as $kfield) {
                $orX->add($qb->expr()->like($kfield,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$filter->getKeywords().'%');
        }

        if (null !== $filter->getSortField()) {
            $qb->orderBy($filter->getSortField(), $filter->getSortOrder());
        }

        return $qb;
    }
}