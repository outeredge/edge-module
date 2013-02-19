<?php

namespace Edge\Filter;

use Zend\Filter\AbstractFilter;

class CsvToArray extends AbstractFilter
{
    /**
     * Convert comma separated string to array
     *
     * @param  string $value
     * @return array
     */
    public function filter($value)
    {
        if (is_string($value)) {
            return explode(',', $value);
        }
        return $value;
    }
}
