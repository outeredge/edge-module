<?php

namespace Edge\Doctrine\Search;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Edge\Search\Exception\IndexException;
use Edge\Search\IndexerInterface;
use Edge\Search\IndexableEntityInterface;

class IndexerSubscriber implements EventSubscriber
{
    /**
     * @var IndexerInterface
     */
    protected $indexer;

    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IndexableEntityInterface) {
            $this->indexer->add($entity);
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IndexableEntityInterface) {
            $this->indexer->update($entity);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IndexableEntityInterface) {
            if ($entity->getUnindexed()) {
                return;
            }

            $this->indexer->delete($entity);

            if ($entity->getUnindexed()) {
                throw new IndexException('Unable to remove entity from CloudSearch');
            }
        }
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove
        );
    }
}