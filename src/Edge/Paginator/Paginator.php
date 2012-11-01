<?php

namespace Edge\Paginator;

use Zend\Paginator\Paginator as ZendPaginator;

class Paginator extends ZendPaginator
{
    /**
     * Convert current page to array
     *
     * @param string $key array key for entities
     * @param mixed $params to pass to the getArrayCopy() function of each entity
     * @return array
     */
    public function toArray($key, $params = null)
    {
        $results = array(
            'pages' => $this->count(),
            'current' => $this->getCurrentPageNumber(),
            'count' => $this->getTotalItemCount(),
            $key => array()
        );

        $currentItems = $this->getCurrentItems();
        foreach ($currentItems as $item) {
            $results[$key][] = $this->getArrayCopy($item, $params);
        }

        return $results;
    }

    public function getArrayCopy($entity, $params)
    {
        return $entity->getArrayCopy($params);
    }
}