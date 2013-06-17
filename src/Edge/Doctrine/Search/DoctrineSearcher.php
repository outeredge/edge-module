<?php

namespace Edge\Doctrine\Search;

use ArrayIterator;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Edge\Search\AbstractSearcher;
use Edge\Search\Exception;
use Edge\Search\Filter;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

class DoctrineSearcher extends AbstractSearcher
{
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
     * @return ArrayIterator
     */
    public function getResults($offset, $itemCountPerPage)
    {
        $qb     = $this->getQueryBuilder();
        $filter = $this->getFilter();
        $orXs   = $qb->expr()->orX();
        $i      = 0;

        foreach ($filter->getAllFieldValues() as $group => $fields) {
            $andXs = $qb->expr()->andX();

            foreach ($fields as $field => $values) {
                $andX = $qb->expr()->andX();
                foreach ($values as $data) {
                    $param       = ':param' . $i++;
                    $value       = $this->prepareValue($data['value'], $field);
                    $mappedField = $this->getMappedField($field);

                    $orX = $qb->expr()->orX();
                    foreach ((array) $mappedField['field'] as $fieldName) {
                        switch ($data['comparison']) {
                            case Filter::COMPARISON_EQUALS:
                                $orX->add($this->getEqualsExpr($fieldName, $value, $param));
                                break;
                            case Filter::COMPARISON_LIKE:
                                $orX->add($this->getLikeExpr($fieldName, $value, $param));
                                break;
                            case Filter::COMPARISON_NOT_EQUALS:
                                $orX->add($this->getNotEqualsExpr($fieldName, $value, $param));
                                break;
                            case Filter::COMPARISON_GREATER:
                                $orX->add($this->getGreaterThanExpr($fieldName, $param));
                                break;
                            case Filter::COMPARISON_GREATER_OR_EQ:
                                $orX->add($this->getGreaterThanOrEqualToExpr($fieldName, $param));
                                break;
                            case Filter::COMPARISON_LESS:
                                $orX->add($this->getLessThanExpr($fieldName, $param));
                                break;
                            case Filter::COMPARISON_LESS_OR_EQ:
                                $orX->add($this->getLassThanOrEqualToExpr($fieldName, $param));
                                break;
                            default:
                                continue;
                                break;
                        }

                        $this->addJoin($fieldName);
                    }

                    $andX->add($orX);

                    if (null !== $value) {
                        $qb->setParameter(trim($param, ':'), $value);
                    }
                }

                $andXs->add($andX);
            }

            if ($group == 0) {
                $qb->andWhere($andXs);
            } else {
                $orXs->add($andXs);
            }
        }

        if (count($orXs)) {
            $qb->andWhere($orXs);
        }

        $keywordFields = $this->getOptions()->getKeywordFields();
        if (!empty($keywordFields) && null !== $filter->getKeywords()) {
            $orX = $qb->expr()->orX();
            foreach ($keywordFields as $field) {
                $this->addJoin($field);
                $orX->add($qb->expr()->like($field,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$filter->getKeywords().'%');
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

        $results = $paginator->getIterator();

        foreach ($this->getConverters() as $converter) {
            $results = $converter->convert($results);
        }

        return $results;
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
     */
    protected function addJoin($field)
    {
        $qb       = $this->getQueryBuilder();
        $joinName = strstr($field, '.', true);

        if ($joinName == $qb->getRootAlias()) {
            return;
        }

        $joinTables = $this->getOptions()->getJoinTables();

        if (isset($this->joins[$joinName]) || !isset($joinTables[$joinName])) {
            return;
        }

        if (substr_count($joinTables[$joinName], '.')) {
            $this->addJoin($joinTables[$joinName]);
            $alias = $joinTables[$joinName];
        } else {
            $alias = $qb->getRootAlias() . '.' . $joinTables[$joinName];
        }

        $qb->leftJoin($alias, $joinName);

        $this->joins[$joinName] = true;
    }

    /**
     * Get a like expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getLikeExpr($field, &$value, $paramName)
    {
        if (null === $value) {
            return $this->getEqualsExpr($field, $value, $paramName);
        }

        $value = '%' . $value . '%';

        return $this->getQueryBuilder()->expr()->like($field, $paramName);
    }

    /**
     * Get an equals expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getEqualsExpr($field, $value, $paramName)
    {
        $expr  = $this->getQueryBuilder()->expr();

        if (null === $value) {
            return $expr->isNull($field);
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
        $expr  = $this->getQueryBuilder()->expr();

        if (null === $value) {
            return $expr->isNotNull($field);
        }

        return $expr->neq($field, $paramName);
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