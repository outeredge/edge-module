<?php

namespace Edge\Doctrine\Search;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreUpdateEventArgs;
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
            if (!$this->indexer->add($entity)) {
                $entity->setUnindexed(true);
            } else {
                $entity->setUnindexed(false);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IndexableEntityInterface) {
            if (!$this->indexer->update($entity)) {
                $entity->setUnindexed(true);
            } else {
                $entity->setUnindexed(false);
            }
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IndexableEntityInterface) {
            if (!$this->indexer->delete($entity)) {
                throw new IndexException('Unable to remove entity from CloudSearch');
            }
        }
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove
        );
    }
}