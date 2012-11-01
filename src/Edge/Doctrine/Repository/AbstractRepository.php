<?php

namespace Edge\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class AbstractRepository extends EntityRepository
{

    const PARAM_SORT  = 'sort';
    const PARAM_ORDER = 'order';

    const ORDER_DESC = 'desc';
    const ORDER_ASC  = 'asc';

    const QUERY_REGEX = '/([a-zA-Z\-]+)(:|!)((?:\([^)]+?\)|[^( ]+))/';

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
     * Google style search by David
     *  - Wrap parameters in brackets if is more than one word
     *  - All non parameter text is gathered into keyword variable
     *
     * @param string $query
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string $sort
     * @param string $order
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByQuery($query, QueryBuilder &$qb, $sort = null, $order = self::ORDER_DESC)
    {
        $query = strtolower($query);
        preg_match_all(self::QUERY_REGEX, $query, $params);
        $keyword = trim(str_replace('  ', ' ', preg_replace(self::QUERY_REGEX, '', $query)));

        $orXs  = array();
        $i = 1;
        foreach ($params[1] as $key => $field) {
            $value = $params[3][$key];
            $i++;

            if ($field == self::PARAM_SORT) {
                if (isset($this->validSearchFields[$value])) {
                    $sort = $this->validSearchFields[$value];
                }
                continue;
            }

            if ($field == self::PARAM_ORDER) {
                if ($value != self::ORDER_DESC) {
                    $order = self::ORDER_ASC;
                }
                continue;
            }

            if (isset($this->validSearchFields[$field])) {
                if (!isset($orXs[$field])) {
                    $orXs[$field] = $qb->expr()->orX();
                }

                $equals = $params[2][$key] == ':' ? true : false;
                if ($equals) {
                    if ($value == 'null') {
                        $orXs[$field]->add($this->validSearchFields[$field] . ' IS NULL');
                    } else {
                        $value = str_replace(array('(', ')'), '', $value);
                        if (strstr($value, ',')) {
                            $orXs[$field]->add($qb->expr()->in($this->validSearchFields[$field], ':'.$field.$i));
                            $qb->setParameter($field . $i, explode(',', $value));
                        } else {
                            $orXs[$field]->add($qb->expr()->eq($this->validSearchFields[$field], ':'.$field.$i));
                            $qb->setParameter($field . $i, $value);
                        }
                    }
                    ;
                } else {
                    if ($value == 'null') {
                        $orXs[$field]->add('NOT '. $this->validSearchFields[$field] . ' IS NULL');
                    } else {
                        $value = str_replace(array('(', ')'), '', $value);
                        if (strstr($value, ',')) {
                            $orXs[$field]->add($qb->expr()->notIn($this->validSearchFields[$field], ':'.$field.$i));
                            $qb->setParameter($field . $i, explode(',', $value));
                        } else {
                            $orXs[$field]->add($qb->expr()->neq($this->validSearchFields[$field], ':'.$field.$i));
                            $qb->setParameter($field . $i, $value);
                        }
                    }
                }
                continue;
            }
        }

        if (count($this->defaultValues)) {
            foreach ($this->defaultValues as $defaultField => $defaultValue) {
                $i++;
                if (!isset($orXs[$defaultField])) {
                    if (isset($this->validSearchFields[$defaultField])) {
                        $orXs[$defaultField] = $qb->expr()->eq($this->validSearchFields[$defaultField], ':'.$defaultField.$i);
                        $qb->setParameter($defaultField . $i, $defaultValue);
                        continue;
                    }
                }
            }
        }

        if (count($orXs)) {
            foreach ($orXs as $where) {
                $qb->andWhere($where);
            }
        }

        if ($keyword != '' && count($this->keywordSearchFields)) {
            $orX = $qb->expr()->orX();
            foreach ($this->keywordSearchFields as $kfield) {
                $orX->add($qb->expr()->like($kfield,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$keyword.'%');
        }

        if (null !== $sort) {
            $qb->orderBy($sort, $order);
        }

        return $qb;
    }

}