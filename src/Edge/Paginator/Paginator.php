<?php

namespace Edge\Paginator;

use Zend\Paginator\Paginator as ZendPaginator;

class Paginator extends ZendPaginator
{
    /**
     * Convert current page to array
     *
     * @param string $key array key for entities
     * @param mixed  $params to pass to the toArray() function of each entity
     * @return array
     */
    public function toArray($key)
    {
        // Get the current items first as count may be retrieved in one call
        $currentItems = $this->getCurrentItems();

        $results = array(
            'pages'   => $this->count(),
            'current' => $this->getCurrentPageNumber(),
            'count'   => $this->getTotalItemCount(),
            $key      => iterator_to_array($currentItems)
        );

        return $results;
    }
}