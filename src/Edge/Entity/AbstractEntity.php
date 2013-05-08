<?php

namespace Edge\Entity;

use ArrayAccess;

abstract class AbstractEntity implements ArrayAccess
{
    public function offsetExists($offset)
    {
        $method = 'get' . ucfirst($offset);
        return in_array($method, get_class_methods($this));
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException("Array access of class " . get_class($this) . " is read-only!");
    }

    public function offsetGet($offset)
    {
        $method = 'get' . ucfirst($offset);
        if (in_array($method, get_class_methods($this))) {
            return $this->$method();
        }
        return null;
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException("Array access of class " . get_class($this) . " is read-only!");
    }

    /**
     * Get an array representation of the entity
     * @todo
     * @return array
     */
    //abstract public function toArray();
}