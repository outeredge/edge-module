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
    protected $joins;

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
        $andXs  = array();
        $i      = 0;

        foreach ($filter->getAllFieldValues() as $field => $values) {
            if (!isset($andXs[$field])) {
                $andXs[$field] = $qb->expr()->andX();
            }

            $this->addJoin($field);

            foreach ($values as $data) {
                $i++;
                $param = ':param' . $i++;
                $value = $this->prepareValue($data['value'], $field);

                $expr = $qb->expr()->orX();
                $mappedField = $this->getMappedField($field);

                foreach ((array) $mappedField['field'] as $fieldName) {
                    switch ($data['comparison']) {
                        case Filter::COMPARISON_EQUALS:
                            $expr->add($this->getEqualsExpr($fieldName, $value, $param));
                            break;
                        case Filter::COMPARISON_LIKE:
                            $expr->add($this->getLikeExpr($fieldName, $value, $param));
                            break;
                        case Filter::COMPARISON_NOT_EQUALS:
                            $expr->add($this->getNotEqualsExpr($fieldName, $value, $param));
                            break;
                        case Filter::COMPARISON_GREATER:
                            $expr->add($this->getGreaterThanExpr($fieldName, $param));
                            break;
                        case Filter::COMPARISON_GREATER_OR_EQ:
                            $expr->add($this->getGreaterThanOrEqualToExpr($fieldName, $param));
                            break;
                        case Filter::COMPARISON_LESS:
                            $expr->add($this->getLessThanExpr($fieldName, $param));
                            break;
                        case Filter::COMPARISON_LESS_OR_EQ:
                            $expr->add($this->getLassThanOrEqualToExpr($fieldName, $param));
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

        $keywordFields = $this->getOptions()->getKeywordFields();
        if (!empty($keywordFields) && null !== $filter->getKeywords()) {
            $orX = $qb->expr()->orX();
            foreach ($keywordFields as $name => $field) {
                $this->addJoin($name);
                $orX->add($qb->expr()->like($field,  ':keyword'));
            }
            $qb->andWhere($orX);
            $qb->setParameter('keyword', '%'.$filter->getKeywords().'%');
        }

        if (null !== $filter->getSortField()) {
            $this->addJoin($filter->getSortField());
            $mappedSortField = $this->getMappedField($filter->getSortField());
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
        if (!substr_count($field, '.')) {
            return;
        }

        $joinName   = strstr($field, '.', true);
        $joinTables = $this->getOptions()->getJoinTables();

        if (isset($this->joins[$joinName]) || !isset($joinTables[$joinName])) {
            return;
        }

        $qb = $this->getQueryBuilder();

        if (is_array($joinTables[$joinName])) {
            $qb->leftJoin(
                $qb->getRootAlias() . '.' . $joinTables[$joinName]['property'],
                $joinTables[$joinName]['alias']
            );
        } else {
            $qb->leftJoin($qb->getRootAlias() . '.' . $joinName, $joinTables[$joinName]);
        }

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
        if (null === $value || is_array($value)) {
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

        if (is_array($value)) {
            if (in_array(null, $value)) {
                return $expr->orX(
                    $expr->isNull($field),
                    $expr->in($field, $paramName)
                );
            } else {
                return $expr->in($field, $paramName);
            }
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