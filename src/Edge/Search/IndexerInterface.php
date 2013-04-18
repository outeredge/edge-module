<?php

namespace Edge\Search;

interface IndexerInterface
{
    public function add(IndexableEntityInterface $entity);

    public function update(IndexableEntityInterface $entity);

    public function delete(IndexableEntityInterface $entity);
}