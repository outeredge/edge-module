<?php

namespace Edge\Search;

interface IndexableEntityInterface
{
    /**
     * @return array
     */
    public function toSearchArray();

    /**
     * Set whether the entity needs to be indexed (true)
     *
     * @param boolean $indexed
     */
    public function setUnindexed($unindexed);

    /**
     * Should return true if the entity needs to be indexed
     *
     * @return boolean
     */
    public function getUnindexed();
}