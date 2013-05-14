<?php
/**
* Google style search
*  - Wrap parameters in brackets if is more than one word
*  - All non parameter text is gathered into keyword variable
*/

namespace Edge\Search;

use Zend\Stdlib\ArrayUtils;
use Zend\Filter\FilterInterface;

class Filter
{
    const PARAM_SORT  = 'sort';
    const PARAM_ORDER = 'order';

    const ORDER_DESC = 'desc';
    const ORDER_ASC  = 'asc';

    const COMPARISON_EQUALS         = ':';
    const COMPARISON_NOT_EQUALS     = '!';
    const COMPARISON_LIKE           = '~';
    const COMPARISON_GREATER        = '>';
    const COMPARISON_LESS           = '<';
    const COMPARISON_GREATER_OR_EQ  = '>=';
    const COMPARISON_LESS_OR_EQ     = '<=';

    const QUERY_REGEX = '/([a-zA-Z-\.]+)(:|!|~|>=|>|<=|<)((?:\([^)]+?\)|[^( ]+))/';

    /**
     * Allowed search fields
     *
     * @var array
     */
    protected $searchFields = array();

    protected $filters = array();

    protected $data = array();

    protected $keywords = null;

    protected $sort = null;

    protected $order = self::ORDER_DESC;


    protected function extract($query)
    {
        $keywords = trim(str_replace('  ', ' ', preg_replace(self::QUERY_REGEX, '', $query)));
        if ($keywords !== '') {
            $this->setKeywords($keywords);
        }

        $params = array();
        preg_match_all(self::QUERY_REGEX, $query, $params);
        foreach ($params[1] as $key => $field) {
            $value = $this->processValue($params[3][$key]);
            $this->addFieldValue($field, $value, $params[2][$key]);
        }
    }

    private function processValue($value)
    {
        $value = str_replace(array('(', ')'), '', $value);

        if ($value == 'null') {
            return null;
        }

        if (strstr($value, ',')) {
            $value = explode(',', $value);
            foreach ($value as $key => &$val) {
                if ($val == 'null') {
                    $val = null;
                }
            }
        }

        return $value;
    }

    /**
     * Add a value to the filter
     *
     * @param string $field
     * @param string $value
     * @param string $comparison see class consts
     * @param boolean $default whether the value should be considered a default
     * @return \Edge\Search\Filter
     */
    public function addFieldValue($field, $value, $comparison = self::COMPARISON_EQUALS, $default = false)
    {
        if ($this->hasSearchField($field)) {
            if (isset($this->data[$field]) && !$default) {
                foreach ($this->data[$field] as $key => $values) {
                    if ($values['default']) {
                        unset($this->data[$field][$key]);
                    }
                }
            }

            $this->data[$field][] = array(
                'value'      => $this->replaceValue($field, $value),
                'comparison' => $comparison,
                'default'    => $default,
            );

            return $this;
        }

        if ($field == self::PARAM_SORT) {
            if ($this->hasSearchField($value)) {
                $this->sort = $value;
            }
            return $this;
        }

        if ($field == self::PARAM_ORDER) {
            if ($value != self::ORDER_DESC) {
                $this->order = self::ORDER_ASC;
            } else {
                $this->order = self::ORDER_DESC;
            }
            return $this;
        }

        return $this;
    }

    public function addStaticFieldValue($field, $value, $comparison = self::COMPARISON_EQUALS)
    {
        $this->data[$field] = array(
            array(
                'value'      => $this->replaceValue($field, $value),
                'comparison' => $comparison,
                'default'    => true
            )
        );
    }

    /**
     * Get all field values, each field contains an array of values
     *
     * @return array
     */
    public function getAllFieldValues()
    {
        return $this->data;
    }

    /**
     * Get an array of values for a specific field
     *
     * @param  string $field
     * @return array
     */
    public function getFieldValues($field)
    {
        if (isset($this->data[$field])) {
            return $this->data[$field];
        }
        return array();
    }

    /**
     * Set the query string to be extracted
     *
     * @param string $query
     * @return \Edge\Search\Filter
     */
    public function setQueryString($query)
    {
        $this->extract($query);
        return $this;
    }

    /**
     * Get the resulting query string
     *
     * @return string
     */
    public function getQueryString()
    {
        $filterStr = '';

        if (empty($this->data)) {
            return $this->getKeywords();
        }

        foreach ($this->data as $field => $values) {
            foreach ($values as $value) {
                $filterStr.= $field . $value['comparison'] . $value['value'] . ' ';
            }
        }

        if (null !== $this->sort) {
            $filterStr.= self::PARAM_SORT . self::COMPARISON_EQUALS . $this->sort . ' ' . self::PARAM_ORDER . self::COMPARISON_EQUALS . $this->order . ' ';
        }

        $filterStr.= $this->getKeywords();

        return trim($filterStr);
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getSortField()
    {
        return $this->sort;
    }

    public function getSortOrder()
    {
        return $this->order;
    }

    public function setSort($sort, $order = self::ORDER_DESC)
    {
        if (!$this->hasSearchField($sort)) {
            throw new Exception\InvalidArgumentException("Invalid sort field [$sort] specified");
        }

        if ($order !== self::ORDER_DESC) {
            $order = self::ORDER_ASC;
        }

        $this->sort  = $sort;
        $this->order = $order;

        return $this;
    }

    public function setSearchFields(array $fields)
    {
        if (ArrayUtils::isList($fields)) {
            $this->searchFields = array_flip($fields);
        } else {
            $this->searchFields = $fields;
        }
        return $this;
    }

    public function getAllSearchFields()
    {
        return $this->searchFields;
    }

    public function hasSearchField($field)
    {
        return isset($this->searchFields[$field]);
    }

    public function getSearchField($field)
    {
        if (!$this->hasSearchField($field)) {
            throw new Exception\InvalidArgumentException("Invalid field [$field] specified");
        }

        return $this->searchFields[$field];
    }

    public function setDefaultValues(array $values)
    {
        foreach ($values as $field => $value) {
            if (is_array($value)) {
                $value = $value['value'];
                $comparison = isset($value['comparison']) ? $value['comparison'] : self::COMPARISON_EQUALS;
            } else {
                $comparison = self::COMPARISON_EQUALS;
            }
            $this->addFieldValue($field, $value, $comparison, true);
        }

        return $this;
    }

    protected function replaceValue($field, $value)
    {
        if ($this->hasValueFilter($field)) {
            $value = $this->getValueFilter($field)->filter($value);
        }
        return $value;
    }

    public function clear()
    {
        $this->data = array();
        return $this;
    }






    public function addValueFilter($field, FilterInterface $filter)
    {
        $this->filters[$field] = $filter;
        return $this;
    }

    public function hasValueFilter($field)
    {
        return array_key_exists($field, $this->filters)
               || array_key_exists('*', $this->filters);
    }

    /**
     * Get value filter for field
     *
     * @param string $field
     * @return FilterInterface
     * @throws Exception\InvalidArgumentException
     */
    public function getValueFilter($field)
    {
        if (isset($this->filters[$field])) {
            return $this->filters[$field];
        }

        if (!isset($this->filters['*'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: no value filter by name of "%s", and no wildcard strategy present',
                __METHOD__,
                $field
            ));
        }

        return $this->filters['*'];
    }

    public function removeValueFilter($field)
    {
        unset($this->filters[$field]);
        return $this;
    }
}