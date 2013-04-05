<?php

namespace Edge\Doctrine\Repository;

use Edge\Entity\AbstractEntity;
use Edge\Entity\Repository\RepositoryInterface;
use Edge\Doctrine\Search\Filter;
use Edge\Service\Exception\DeleteException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
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
     * Mappings between entity property and join alias
     * @var array
     */
    protected static $joinTableAliases = array();

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
     * Create a filtered query builder
     *
     * @param string $query
     * @param string $alias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createFilteredQueryBuilder($query, $alias)
    {
        $qb     = $this->createQueryBuilder($alias);
        $filter = $this->getFilter();
        $filter->setMetaData($this->getClassMetadata())
               ->setQueryString($query);

        return $filter->populateQueryBuilder($qb);
    }

    /**
     * Get Filter class populated with repositories search fields
     *
     * @return Filter
     */
    public static function getFilter()
    {
        if (null === static::$filter) {
            $filter = new Filter();
            $filter->setValidSearchFields(static::$validSearchFields);;
            $filter->setDefaultValues(static::$defaultValues);
            $filter->setJoinTableAliases(static::$joinTableAliases);
            $filter->setKeywordSearchFields(static::$keywordSearchFields);
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
     * Delete an entity in the database
     *
     * @param AbstractEntity $entity
     * @param boolean $immediate
     */
    public function delete(AbstractEntity $entity, $immediate = true)
    {
        $this->getEntityManager()->remove($entity);

        try {
            $this->flush(null, $immediate);
        } catch (DBALException $ex) {
            throw new DeleteException('Unable to delete entity', null, $ex);
        }

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