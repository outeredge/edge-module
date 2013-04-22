<?php

namespace Edge\Search;

interface IndexerInterface
{
    /**
     * Add an entity or array of entities to the index,
     * entities must be of type IndexableEntityInterface
     *
     * @param array|IndexableEntityInterface $entities
     */
    public function add($entities);

    /**
     * Update an entity or array of entities in the index,
     * entities must be of type IndexableEntityInterface
     *
     * @param array|IndexableEntityInterface $entities
     */
    public function update($entities);

    /**
     * Delete an entity or array of entities from the index,
     * entities must be of type IndexableEntityInterface
     *
     * @param array|IndexableEntityInterface $entities
     */
    public function delete($entities);
}