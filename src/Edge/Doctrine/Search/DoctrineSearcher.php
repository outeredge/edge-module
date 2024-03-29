<?php

namespace Edge\Doctrine\Search;

use ArrayIterator;
use DateTime;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Edge\Search\AbstractSearcher;
use Edge\Search\Exception;
use Edge\Search\Filter;

class DoctrineSearcher extends AbstractSearcher
{
    const FIELD_TYPE_BOOLEAN  = 'bool';
    const FIELD_TYPE_DATE     = 'date';
    const FIELD_TYPE_FULLTEXT = 'fulltext';
    const FIELD_TYPE_INTEGER  = 'int';

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
        $i      = 0;

        if ($this->hasResults) {
            $qb->getEntityManager()->clear();
        }

        foreach ($filter->getAllFieldValues() as $group => $fields) {
            $expressions = $group === 0 ? $qb->expr()->andX() : $qb->expr()->orX();

            foreach ($fields as $field => $values) {
                foreach ($values as $data) {
                    $param       = ':param' . $i++;
                    $value       = $this->prepareValue($data['value'], $field);
                    $mappedField = $this->getMappedField($field);

                    foreach ((array) $mappedField['field'] as $fieldName) {
                        $this->addJoin($fieldName, $group);

                        if (!isset($this->conditionalFields[$fieldName])) {
                            $expressions->add($this->getExpression($fieldName, $data['comparison'], $value, $param, $mappedField['type']));
                        } else {
                            $value = null;
                        }
                    }

                    if (null !== $value) {
                        $qb->setParameter(trim($param, ':'), $value);
                    }
                }
            }

            $qb->andWhere($expressions);
        }

        $hasFulltext   = false;
        $keywordFields = $this->getOptions()->getKeywordFields();
        if (!empty($keywordFields) && null !== $filter->getKeywords()) {
            $orX = $qb->expr()->orX();

            foreach ($keywordFields as $field) {
                $mappedField = $this->getMappedField($field);
                foreach ((array) $mappedField['field'] as $fieldName) {
                    $this->addJoin($fieldName);
                    if ($mappedField['type'] === self::FIELD_TYPE_FULLTEXT) {
                        $hasFulltext = true;
                        $orX->add($qb->expr()->andX(
                            sprintf('MATCH (%s) AGAINST (%s) > 1', $fieldName, ':fulltext_keyword'),
                            sprintf('%s IS NOT NULL', $fieldName)
                        ));
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

        $query     = $qb->getQuery();
        $paginator = new DoctrinePaginator($query, false);
        $results   = $paginator->setUseOutputWalkers(true)->getIterator();

        $this->count = $paginator->count();
        if (0 == $this->count) {
            return new ArrayIterator([]);
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
            case Filter::COMPARISON_NOT_LIKE:
                return $this->getNotLikeExpr($field, $value, $param, $type);
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

        if ($joinAlias == $qb->getRootAliases()[0]) {
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

                    $mappedField = $this->getMappedField($joinConditionals[$joinAlias])['field'];

                    $joinCondition = $this->getExpression(
                        $mappedField,
                        $conditionValues[0]['comparison'],
                        $conditionValues[0]['value'],
                        $conditionValues[0]['value']
                    );

                    $this->conditionalFields[$mappedField] = true;
                }
            }

            $qb->leftJoin($qb->getRootAliases()[0] . '.' . $joinTable, $groupAlias, Join::WITH, $joinCondition);
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
        $qb   = $this->getQueryBuilder();
        $expr = $qb->expr();

        if (null === $value) {
            return $expr->isNotNull($field);
        }

        // Check if we need to use NOT EXISTS
        $fieldVals = explode('.', $field);

        $rootAlias = $qb->getRootAliases()[0];
        $entity    = $qb->getRootEntities()[0];
        $meta      = $qb->getEntityManager()->getClassMetadata($entity);
        $metaAssoc = $meta->getAssociationMappings();
        $joins     = $this->getOptions()->getJoinTables();

        if ($fieldVals[0] != $rootAlias
            && isset($joins[$fieldVals[0]])
            && isset($metaAssoc[$joins[$fieldVals[0]]]['targetEntity'])
            && $qb->getEntityManager()->getClassMetadata($metaAssoc[$joins[$fieldVals[0]]]['targetEntity'])->getSingleIdentifierFieldName() == $fieldVals[1]
        ) {
            $subRoot = uniqid('sub');

            $sub = $qb->getEntityManager()->createQueryBuilder();
            $sub->select($subRoot)
                ->from($entity, $subRoot)
                ->innerJoin($subRoot . '.' . $joins[$fieldVals[0]], $subRoot . 'join')
                ->andWhere($qb->expr()->eq(
                    $subRoot . 'join' . '.' . $fieldVals[1],
                    $paramName
                ))
                ->andWhere($qb->expr()->eq(
                    $subRoot . '.' . $meta->getSingleIdentifierFieldName(),
                    $rootAlias . '.' . $meta->getSingleIdentifierFieldName()
                ));

            return $expr->not($qb->expr()->exists($sub->getDQL()));
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
        if (null === $value || $type == self::FIELD_TYPE_INTEGER) {
            return $this->getEqualsExpr($field, $value, $paramName);
        }

        if ($type == self::FIELD_TYPE_FULLTEXT) {
            return "MATCH ($field) AGAINST ($paramName) > 1";
        }

        $value = '%' . $value . '%';

        return $this->getQueryBuilder()->expr()->like($field, $paramName);
    }

    /**
     * Get a not like expression for specified field and data
     *
     * @param string  $field field name
     * @param mixed   $value search value
     * @param string  $paramName parameter name to use
     */
    protected function getNotLikeExpr($field, &$value, $paramName, $type = null)
    {
        if (null === $value || $type == self::FIELD_TYPE_INTEGER) {
            return $this->getNotEqualsExpr($field, $value, $paramName);
        }

        $value = '%' . $value . '%';

        return $this->getQueryBuilder()->expr()->notLike($field, $paramName);
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
