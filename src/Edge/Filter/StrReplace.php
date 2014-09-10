<?php

namespace Edge\Filter;

use Zend\Filter\FilterInterface;

class StrReplace implements FilterInterface
{
    protected $search;

    protected $replace;

    public function __construct($search, $replace)
    {
        $this->search  = $search;
        $this->replace = $replace;
    }

    public function filter($value)
    {
        if (null !== $value) {
            $value = str_replace($this->search, $this->replace, $value);
        }
        return $value;
    }
}