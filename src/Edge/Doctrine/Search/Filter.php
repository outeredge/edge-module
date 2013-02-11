<?php

namespace Edge\Doctrine\Search;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Edge\Search\Filter as BaseFilter;

class Filter extends BaseFilter
{
    protected $joinTableAliases = array();

    protected $keywordSearchFields = array();

    /**
     * Apply filtered values to a QueryBuilder
     *
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function populateQueryBuilder(QueryBuilder $qb)
    {
        $orXs  = array();
        $joins = array();
        $i = 0;

        foreach ($this->getAllFieldValues() as $field => $data) {
            $i++;
            $paramName = 'param'.$i;

            if (!isset($orXs[$field])) {
                $orXs[$field] = $qb->expr()->orX();
            }

            if (substr_count($field, '.')) {
                $joinAlias = strstr($field, '.', true);
                if (!isset($joins[$joinAlias]) && isset($this->joinTableAliases[$joinAlias])) {
                    $qb->join($qb->getRootAlias() . '.' . $joinAlias, $this->joinTableAliases[$joinAlias]);
                    $joins[$joinAlias] = true;
                }
            }

            if ($data['comparison'] == self::COMPARISON_EQUALS) {
                $expr = $this->getEqualsExpr($this->validSearchFields[$field], $data['value'], $paramName, $qb->expr());
            } elseif ($data['comparison'] == self::COMPARISON_LIKE) {
                $data['value'] = '%'. $data['value'] .'%';
                $expr = $this->getLikeExpr($this->validSearchFields[$field], $data['value'], $paramName, $qb->expr());
            } else {
                $expr = $this->getNotEqualsExpr($this->validSearchFields[$field], $data['value'], $paramName, $qb->expr());
            }

            if (null !== $data['value']) {
                $qb->setParameter($paramName, $data['value']);
            }

            $orXs[$field]->add($expr);
        }

        if (count($orXs)) {
            foreach ($orXs as $where) {
                $qb->andWhere($where);
            }
        }

        if (null !== $this->getKeywords() && !empty($this->keywordSearchFields)) {
            $orX = $qb->expr()->orX();
            foreach ($this->keywordSearchFields as $kfield) {
                $orX->add($qb->expr()->like($kfield,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$this->getKeywords().'%');
        }

        if (null !== $this->getSortField()) {
            $qb->orderBy($this->validSearchFields[$this->getSortField()], $this->getSortOrder());
        }

        return $qb;
    }

    /**
     * Get a like expression for specified field and data
     *
     * @param string  $field field name on entity
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     * @param Expr    $expr
     */
    protected function getLikeExpr($field, $value, $paramName, Expr $expr)
    {
        if (null === $value || is_array($value)) {
            return $this->getEqualsExpr($field, $value, $paramName, $expr);
        }

        return $expr->like($field, ':'.$paramName);
    }

    /**
     * Get an equals expression for specified field and data
     *
     * @param string  $field field name on entity
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     * @param Expr    $expr
     */
    protected function getEqualsExpr($field, $value, $paramName, Expr $expr)
    {
        if (null === $value) {
            return $field . ' IS NULL';
        }

        if (is_array($value)) {
            if (in_array(null, $value)) {
                return $expr()->orX(
                    $field . ' IS NULL',
                    $expr->in($field, ':'.$paramName)
                );
            }
            return $expr->in($field, ':'.$paramName);
        }

        return $expr->eq($field, ':'.$paramName);
    }

    /**
     * Get a not-equals expression for specified field and data
     *
     * @param string  $field field name on entity
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     * @param Expr    $expr
     */
    protected function getNotEqualsExpr($field, $value, $paramName, Expr $expr)
    {
        if (null === $value) {
            return 'NOT '. $field . ' IS NULL';
        }

        if (is_array($value)) {
            if (in_array(null, $value)) {
                return $expr()->orX(
                    'NOT '. $field . ' IS NULL',
                    $expr->notIn($field, ':'.$paramName)
                );
            }
            return $expr->notIn($field, ':'.$paramName);
        }

        return $expr->neq($field, ':'.$paramName);
    }

    public function setKeywordSearchFields(array $fields)
    {
        $this->keywordSearchFields = $fields;
    }

    public function setJoinTableAliases(array $tables)
    {
        $this->joinTableAliases = $tables;
    }
}