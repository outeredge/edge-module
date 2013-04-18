<?php

namespace Edge\Doctrine\Search\Indexer;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Edge\Search\Indexer\IndexerInterface;
use Edge\Search\Indexer\IndexableEntityInterface;

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
            $this->indexer->delete($entity);
            //@todo throw exception here if failure?
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