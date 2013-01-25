<?php

namespace Edge\Doctrine\Repository;

use Edge\Entity\Repository\RepositoryInterface;
use Edge\Search\Filter;
use Edge\Entity\AbstractEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

abstract class AbstractDoctrineRepository extends EntityRepository implements RepositoryInterface, EventManagerAwareInterface
{
    /**
     * Allowed search fields
     * @var array
     */
    protected static $validSearchFields = array();

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
     * @var Filter
     */
    protected static $filter;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Event identifier
     *
     * @var string
     */
    protected $eventIdentifier = 'Edge';

     /**
     * Set the event manager instance used by this context
     *
     * @todo PHP5.4 traits can be used here for events
     * @param EventManagerInterface $events
     * @return mixed
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $identifiers = array(__CLASS__, get_called_class());
        if (isset($this->eventIdentifier)) {
            if ((is_string($this->eventIdentifier))
                || (is_array($this->eventIdentifier))
                || ($this->eventIdentifier instanceof Traversable)
            ) {
                $identifiers = array_unique(array_merge($identifiers, (array) $this->eventIdentifier));
            } elseif (is_object($this->eventIdentifier)) {
                $identifiers[] = $this->eventIdentifier;
            }
            // silently ignore invalid eventIdentifier types
        }
        $events->setIdentifiers($identifiers);
        $this->events = $events;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events instanceof EventManagerInterface) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

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
        if (null === static::$filter || null !== $query) {
            $filter = new Filter();
            $filter->setValidSearchFields(static::$validSearchFields);;
            $filter->setDefaultValues(static::$defaultValues);
            if (null !== $query) {
                $filter->setQueryString($query);
            }
            static::$filter = $filter;
        }
        return static::$filter;
    }

    /**
     * Store a new Entity in the database
     *
     * @param AbstractEntity $entity
     */
    public function save(AbstractEntity $entity, $immediate = true)
    {
        $this->getEntityManager()->persist($entity);
        $this->flush($entity, $immediate);
        return $this;
    }

    /**
     * Update an existing entity in the database (delayed)
     *
     * @param AbstractEntity $entity
     */
    public function update(AbstractEntity $entity, $immediate = false)
    {
        $this->flush($entity, $immediate);
        return $this;
    }

    /**
     * Delete an entity in the database (delayed)
     *
     * @param AbstractEntity $entity
     */
    public function delete(AbstractEntity $entity, $immediate = false)
    {
        $this->getEntityManager()->remove($entity);
        $this->flush($entity, $immediate);
        return $this;
    }

    /**
     * Flush an entity, or all entities to the database
     *
     * @param AbstractEntity $entity entity to flush, leave null for all
     * @param bool $immediate immediately flush the entity(s)
     */
    protected function flush(AbstractEntity $entity = null, $immediate = false)
    {
        $params = compact('entity', 'immediate');
        $this->getEventManager()->trigger(__FUNCTION__, $this->getEntityManager(), $params);
        return $this;
    }
}