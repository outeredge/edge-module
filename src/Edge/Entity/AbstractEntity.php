<?php

namespace Edge\Entity;

use ArrayAccess;
use BadMethodCallException;

abstract class AbstractEntity implements ArrayAccess
{
    abstract public function getId();

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
    
    public function getEntityShortName()
    {
        return substr(strrchr(static::class, '\\'), 1);
    }
}
