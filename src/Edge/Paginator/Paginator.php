<?php

namespace Edge\Paginator;

use Zend\Paginator\Paginator as ZendPaginator;

class Paginator extends ZendPaginator
{
    /**
     * Convert current page to array
     *
     * @param string $key [optional] array key for entities
     * @return array
     */
    public function toArray($key = null)
    {
        // Get the current items first as count may be retrieved in one call
        $currentItems = iterator_to_array($this->getCurrentItems());

        if (null === $key) {
            return $currentItems;
        }

        $results = array(
            'pages'   => $this->count(),
            'current' => $this->getCurrentPageNumber(),
            'count'   => $this->getTotalItemCount(),
            $key      => $currentItems
        );

        return $results;
    }
}