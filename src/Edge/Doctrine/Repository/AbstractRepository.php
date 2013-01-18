<?php

namespace Edge\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Edge\Search\Filter;

abstract class AbstractRepository extends EntityRepository
{
    /**
     * Allowed search fields
     * @var array
     */
    protected static $validSearchFields = array('test');

    /**
     * Fields to apply a %like% search to
     * @var array
     */
    protected static $keywordSearchFields = array();

    /**
     * Array of field names and their default values
     * @var array
     */
    protected static $defaultValues = array();

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
                    $orXs[$field]->add(static::$validSearchFields[$field] . ' IS NULL');
                } else {
                    if (is_array($value)) {
                        $orXs[$field]->add($qb->expr()->in(static::$validSearchFields[$field], ':'.$field.$i));
                    } else {
                        $orXs[$field]->add($qb->expr()->eq(static::$validSearchFields[$field], ':'.$field.$i));
                    }
                    $qb->setParameter($field . $i, $value);
                }
            } else {
                if (null === $value) {
                    $orXs[$field]->add('NOT '. static::$validSearchFields[$field] . ' IS NULL');
                } else {
                    if (is_array($value)) {
                        $orXs[$field]->add($qb->expr()->notIn(static::$validSearchFields[$field], ':'.$field.$i));
                    } else {
                        $orXs[$field]->add($qb->expr()->neq(static::$validSearchFields[$field], ':'.$field.$i));
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

        if (null !== $filter->getKeywords() && count(static::$keywordSearchFields)) {
            $orX = $qb->expr()->orX();
            foreach (static::$keywordSearchFields as $kfield) {
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

    public static function getFilter($query = null)
    {
        $filter = new Filter();
        $filter->setValidSearchFields(static::$validSearchFields);;
        $filter->setDefaultValues(static::$defaultValues);
        if (null !== $query) {
            $filter->setQueryString($query);
        }
        return $filter;
    }
}