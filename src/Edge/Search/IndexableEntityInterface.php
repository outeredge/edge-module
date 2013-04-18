<?php

namespace Edge\Search;

interface IndexableEntityInterface
{
    /**
     * @return array
     */
    public function toSearchArray();

    /**
     * Set whether the entity has been indexed
     *
     * @param boolean $indexed
     */
    public function setIndexed($indexed = true);
}