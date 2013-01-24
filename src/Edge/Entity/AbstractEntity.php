<?php

namespace Edge\Entity;

use ArrayAccess;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

abstract class AbstractEntity implements ArrayAccess, InputFilterAwareInterface
{
    /**
     * @var InputFilterInterface
     */
    protected $inputFilter;

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

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    public function getInputFilter()
    {
        if (null === $this->inputFilter) {
            $this->inputFilter = new InputFilter();
        }
        return $this->inputFilter;
    }
}