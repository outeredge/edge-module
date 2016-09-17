<?php

namespace Edge\Doctrine\Search;

use ArrayIterator;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Edge\Search\AbstractSearcher;
use Edge\Search\Exception;
use Edge\Search\Filter;

class DoctrineSearcher extends AbstractSearcher
{
    const FIELD_TYPE_BOOLEAN  = 'bool';
    const FIELD_TYPE_DATE     = 'date';
    const FIELD_TYPE_FULLTEXT = 'fulltext';

    /**
     * @var DoctrineSearcherOptions
     */
    protected $options;

    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var array
     */
    protected $joins = array();

    /**
     * @var array
     */
    protected $conditionalFields = array();

    /**
     * @var boolean
     */
    protected $hasResults = false;

    /**
     * Set options
     *
     * @param DoctrineSearcherOptions|array $options
     */
    public function setOptions($options)
    {
        if (!$options instanceof DoctrineSearcherOptions) {
            $options = new DoctrineSearcherOptions($options);
        }
        $this->options = $options;
    }

    /**
     * @return DoctrineSearcherOptions
     * @throws RuntimeException
     */
    public function getOptions()
    {
        if (null === $this->options) {
            throw new Exception\RuntimeException('No options were specified');
        }
        return $this->options;
    }

    /**
     * Set query builder to be used for obtaining results
     *
     * @param QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;
        return $this;
    }

    public function getQueryBuilder()
    {
        if (null === $this->qb) {
            throw new Exception\RuntimeException('No query builder instance available');
        }
        return $this->qb;
    }

    /**
     * Get all results from offset
     *
     * NOTE: Looping pages in a single request will force the ENTIRE EntityManager to be cleared for memory saving purposes
     *
     * @return ArrayIterator
     */
    public function getResults($offset, $itemCountPerPage)
    {
        $qb     = $this->getQueryBuilder();
        $filter = $this->getFilter();
        $orXs   = $qb->expr()->orX();
        $i      = 0;

        if ($this->hasResults) {
            $qb->getEntityManager()->clear();
        }

        foreach ($filter->getAllFieldValues() as $group) {
            $exprs = $qb->expr()->andX();

            foreach ($group['fields'] as $field => $values) {
                $expr = $group['mode'] == Filter::GROUP_MODE_AND ? $qb->expr()->andX() : $qb->expr()->orX();
                foreach ($values as $data) {
                    $param       = ':param' . $i++;
                    $value       = $this->prepareValue($data['value'], $field);
                    $mappedField = $this->getMappedField($field);
                    $orX         = $qb->expr()->orX();

                    foreach ((array) $mappedField['field'] as $fieldName) {
                        $this->addJoin($fieldName, $group);
                        if (!isset($this->conditionalFields[$fieldName])) {
                            $orX->add($this->getExpression($fieldName, $data['comparison'], $value, $param, $mappedField['type']));
                        } else {
                            $value = null;
                        }
                    }

                    $expr->add($orX);

                    if (null !== $value) {
                        $qb->setParameter(trim($param, ':'), $value);
                    }
                }

                $exprs->add($expr);
            }

            if ($group == 0) {
                $qb->andWhere($exprs);
            } else {
                $orXs->add($exprs);
            }
        }

        if ($orXs->count()) {
            $qb->andWhere($orXs);
        }

        $keywordFields = $this->getOptions()->getKeywordFields();
        if (!empty($keywordFields) && null !== $filter->getKeywords()) {
            $hasFulltext = false;
            $orX = $qb->expr()->orX();

            foreach ($keywordFields as $field) {
                $mappedField = $this->getMappedField($field);
                foreach ((array) $mappedField['field'] as $fieldName) {
                    $this->addJoin($fieldName);
                    if ($mappedField['type'] === self::FIELD_TYPE_FULLTEXT) {
                        $hasFulltext = true;
                        $orX->add(sprintf('MATCH (%s) AGAINST (%s) > 1', $fieldName, ':fulltext_keyword'));
                    } else {
                        $orX->add($qb->expr()->like($fieldName,  ':keyword'));
                    }
                }
            }

            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$filter->getKeywords().'%');

            if ($hasFulltext) {
                $qb->setParameter('fulltext_keyword', $filter->getKeywords());
            }
        }

        if (null !== $filter->getSortField()) {
            $mappedSortField = $this->getMappedField($filter->getSortField());
            $this->addJoin($mappedSortField['field']);
            $qb->orderBy($mappedSortField['field'], $filter->getSortOrder());
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($itemCountPerPage);

        $paginator = new DoctrinePaginator($qb, count($qb->getDQLPart('join')));
        $paginator->setUseOutputWalkers(false);

        $this->count = $paginator->count();

        if (0 == $this->count) {
            return new ArrayIterator(array());
        }

        try {
            $results = $paginator->getIterator();
        } catch (\RuntimeException $ex) {
            $paginator->setUseOutputWalkers(true);
            $results = $paginator->getIterator();
        }

        foreach ($this->getConverters() as $converter) {
            $results = $converter->convert($results);
        }

        $this->hasResults = true;

        return $results;
    }

    /**
     * Get expression object for field
     *
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     * @param string $param
     * @param string $type
     * @throws Exception\InvalidArgumentException
     */
    protected function getExpression($field, $operator, &$value, $param, $type = null)
    {
        switch ($operator) {
            case Filter::COMPARISON_EQUALS:
                return $this->getEqualsExpr($field, $value, $param, $type);
            case Filter::COMPARISON_NOT_EQUALS:
                return $this->getNotEqualsExpr($field, $value, $param);
            case Filter::COMPARISON_LIKE:
                return $this->getLikeExpr($field, $value, $param, $type);
            case Filter::COMPARISON_GREATER:
                return $this->getGreaterThanExpr($field, $param);
            case Filter::COMPARISON_GREATER_OR_EQ:
                return $this->getGreaterThanOrEqualToExpr($field, $param);
            case Filter::COMPARISON_LESS:
                return $this->getLessThanExpr($field, $param);
            case Filter::COMPARISON_LESS_OR_EQ:
                return $this->getLassThanOrEqualToExpr($field, $param);
            default:
                throw new Exception\InvalidArgumentException('Invalid operator specified');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTypeConversions($value, $type)
    {
        switch($type) {
            case 'datetime':
            case 'date':
                if (!$value instanceof DateTime && null !== $value) {
                    $value = new DateTime($value);
                }
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * Selectively add a join onto the qb
     *
     * @param string $field
     * @param int $group
     */
    protected function addJoin(&$field, $group = 0)
    {
        $qb = $this->getQueryBuilder();

        $joinTables = $this->getOptions()->getJoinTables();
        $joinAlias  = strstr($field, '.', true);

        if ($joinAlias == $qb->getRootAlias()) {
            return;
        }

        if($group != 0) {
            $groupAlias = $joinAlias . $group;
            $field = str_replace($joinAlias . '.', $groupAlias . '.', $field);
        } else {
            $groupAlias = $joinAlias;
        }

        if (isset($this->joins[$groupAlias])) {
            return;
        }

        if (!isset($joinTables[$joinAlias])) {
            throw new Exception\InvalidArgumentException('Invalid join table specified');
        }

        $joinTable = $joinTables[$joinAlias];

        if (substr_count($joinTable, '.')) {
            $this->addJoin($joinTable, $group);
            $qb->leftJoin($joinTable, $groupAlias);
        } else {
            // check if join should be conditional
            $joinCondition    = null;
            $joinConditionals = $this->getOptions()->getJoinConditionals();
            if (isset($joinConditionals[$joinAlias])) {
                $conditionValues = $this->getFilter()->getFieldValues($joinConditionals[$joinAlias], $group);
                if (!empty($conditionValues)) {
                    if (count($conditionValues) > 1) {
                        throw new Exception\InvalidArgumentException('Too many conditions for join');
                    }

                    $joinCondition = $this->getExpression(
                        $field,
                        $conditionValues[0]['comparison'],
                        $conditionValues[0]['value'],
                        $conditionValues[0]['value']
                    );

                    $this->conditionalFields[$field] = true;
                }
            }

            $qb->leftJoin($qb->getRootAlias() . '.' . $joinTable, $groupAlias, Join::WITH, $joinCondition);
        }

        $this->joins[$groupAlias] = true;
    }

    /**
     * Get an equals expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getEqualsExpr($field, $value, $paramName, $type = null)
    {
        $expr = $this->getQueryBuilder()->expr();

        if (null === $value) {
            return $expr->isNull($field);
        }

        if ($type == self::FIELD_TYPE_BOOLEAN && $value == 0) {
            return $expr->orX(
                $expr->eq($field, $paramName),
                $expr->isNull($field)
            );
        }

        return $expr->eq($field, $paramName);
    }

    /**
     * Get a not-equals expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getNotEqualsExpr($field, $value, $paramName)
    {
        $expr = $this->getQueryBuilder()->expr();

        if (null === $value) {
            return $expr->isNotNull($field);
        }

        return $expr->neq($field, $paramName);
    }

    /**
     * Get a like expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getLikeExpr($field, &$value, $paramName, $type = null)
    {
        if (null === $value) {
            return $this->getEqualsExpr($field, $value, $paramName);
        }

        if ($type == self::FIELD_TYPE_FULLTEXT) {
            return "MATCH ($field) AGAINST ($paramName) > 1";
        }

        $value = '%' . $value . '%';

        return $this->getQueryBuilder()->expr()->like($field, $paramName);
    }

    protected function getGreaterThanExpr($field, $paramName)
    {
        return $this->getQueryBuilder()->expr()->gt($field, $paramName);
    }

    protected function getGreaterThanOrEqualToExpr($field, $paramName)
    {
        return $this->getQueryBuilder()->expr()->gte($field, $paramName);
    }

    protected function getLessThanExpr($field, $paramName)
    {
        return $this->getQueryBuilder()->expr()->lt($field, $paramName);
    }

    protected function getLassThanOrEqualToExpr($field, $paramName)
    {
        return $this->getQueryBuilder()->expr()->lte($field, $paramName);
    }
}
