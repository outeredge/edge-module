<?php

namespace Edge\Doctrine\Search;

use DateTime;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Edge\Search\Filter as BaseFilter;

/**
 * Extension for \Edge\Search\Filter that creates a QB instance based on the Filter
 */
class Filter extends BaseFilter
{
    protected $joinTableAliases = array();

    protected $keywordSearchFields = array();

    protected $joins = array();

    protected $metadata = null;

    /**
     * Apply filtered values to a QueryBuilder
     *
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function populateQueryBuilder(QueryBuilder $qb)
    {
        $andXs  = array();
        $i = 0;

        foreach ($this->getAllFieldValues() as $field => $values) {

            if (!isset($andXs[$field])) {
                $andXs[$field] = $qb->expr()->andX();
            }

            $this->addJoin($field, $qb);

            foreach ($values as $data) {
                $i++;

                $param = ':param' . $i;
                $value = $this->handleTypeConversions($data['value'], $field);
                $expr  = $qb->expr()->orX();

                foreach ((array) $this->validSearchFields[$field] as $searchField) {
                    switch ($data['comparison']) {
                        case self::COMPARISON_EQUALS:
                            $expr->add($this->getEqualsExpr($searchField, $value, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_LIKE:
                            $expr->add($this->getLikeExpr($searchField, $value, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_NOT_EQUALS:
                            $expr->add($this->getNotEqualsExpr($searchField, $value, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_GREATER:
                            $expr->add($this->getGreaterThanExpr($searchField, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_GREATER_OR_EQ:
                            $expr->add($this->getGreaterThanOrEqualToExpr($searchField, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_LESS:
                            $expr->add($this->getLessThanExpr($searchField, $param, $qb->expr()));
                            break;
                        case self::COMPARISON_LESS_OR_EQ:
                            $expr->add($this->getLassThanOrEqualToExpr($searchField, $param, $qb->expr()));
                            break;
                        default:
                            continue;
                            break;
                    }
                }

                $andXs[$field]->add($expr);

                if (null !== $value) {
                    $qb->setParameter(trim($param, ':'), $value);
                }
            }
        }

        if (count($andXs)) {
            foreach ($andXs as $where) {
                $qb->andWhere($where);
            }
        }

        if (null !== $this->getKeywords() && !empty($this->keywordSearchFields)) {
            $orX = $qb->expr()->orX();
            foreach ($this->keywordSearchFields as $name => $field) {
                $this->addJoin($name, $qb);
                $orX->add($qb->expr()->like($field,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$this->getKeywords().'%');
        }

        if (null !== $this->getSortField()) {
            $this->addJoin($this->getSortField(), $qb);
            $qb->orderBy($this->validSearchFields[$this->getSortField()], $this->getSortOrder());
        }

        return $qb;
    }

    /**
     * Selectively add a join onto a qb instance using the aliases specified
     *
     * @param string $field
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addJoin($field, QueryBuilder $qb)
    {
        if (!substr_count($field, '.')) {
            return $qb;
        }

        $joinName = strstr($field, '.', true);

        if (isset($this->joins[$joinName]) || !isset($this->joinTableAliases[$joinName])) {
            return $qb;
        }

        if (is_array($this->joinTableAliases[$joinName])) {
            $qb->leftJoin(
                $qb->getRootAlias() . '.' . $this->joinTableAliases[$joinName]['property'],
                $this->joinTableAliases[$joinName]['alias']
            );
        } else {
            $qb->leftJoin($qb->getRootAlias() . '.' . $joinName, $this->joinTableAliases[$joinName]);
        }

        $this->joins[$joinName] = true;
        return $qb;
    }

    /**
     * Handle conversions of strings to relevant field types
     *
     * @param  string  $value
     * @param  string  $field
     * @return mixed
     */
    protected function handleTypeConversions($value, $field)
    {
        switch($this->getMetaData()->getTypeOfField($field)) {
            case 'datetime':
            case 'date':
                $value = new DateTime($value);
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * Get a like expression for specified field and data
     *
     * @param string  $field field name on entity
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     * @param Expr    $expr
     */
    protected function getLikeExpr($field, &$value, $paramName, Expr $expr)
    {
        if (null === $value || is_array($value)) {
            return $this->getEqualsExpr($field, $value, $paramName, $expr);
        }

        $value = '%' . $value . '%';

        return $expr->like($field, $paramName);
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
            return $expr->isNull($field);
        }

        if (is_array($value)) {
            if (in_array(null, $value)) {
                return $expr->orX(
                    $expr->isNull($field),
                    $expr->in($field, $paramName)
                );
            }
            return $expr->in($field, $paramName);
        }

        return $expr->eq($field, $paramName);
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
            return $expr->isNotNull($field);
        }

        if (is_array($value)) {
            if (in_array(null, $value)) {
                return $expr()->orX(
                    $expr->isNotNull($field),
                    $expr->notIn($field, $paramName)
                );
            }
            return $expr->notIn($field, $paramName);
        }

        return $expr->neq($field, $paramName);
    }

    protected function getGreaterThanExpr($field, $paramName, Expr $expr)
    {
        return $expr->gt($field, $paramName);
    }

    protected function getGreaterThanOrEqualToExpr($field, $paramName, Expr $expr)
    {
        return $expr->gte($field, $paramName);
    }

    protected function getLessThanExpr($field, $paramName, Expr $expr)
    {
        return $expr->lt($field, $paramName);
    }

    protected function getLassThanOrEqualToExpr($field, $paramName, Expr $expr)
    {
        return $expr->lte($field, $paramName);
    }

    public function setKeywordSearchFields(array $fields)
    {
        $this->keywordSearchFields = $fields;
        return $this;
    }

    public function setJoinTableAliases(array $tables)
    {
        $this->joinTableAliases = $tables;
        return $this;
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     * @return Filter
     */
    public function setMetaData(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return ClassMetadata
     */
    public function getMetaData()
    {
        return $this->metadata;
    }
}