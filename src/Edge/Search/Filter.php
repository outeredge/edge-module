<?php
/**
* Google style search
*  - Wrap parameters in square brackets if is more than one word
*  - All non parameter text is gathered into keyword variable
*/

namespace Edge\Search;

use Zend\Stdlib\ArrayUtils;

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

    const OPERATOR_AND              = 'AND';
    const OPERATOR_OR               = 'OR';

    const FILTER_REGEX = '/(OR|AND|) ?([a-zA-Z-\.]+)(:|!|~|>=|>|<=|<)((?:\[[^)]+?\]|[^\[\]\s]+))/';
    const GROUP_REGEX  = '/(|AND|OR) ?(?:\(([^()]*)\))/';

    /**
     * Allowed search fields
     *
     * @var array
     */
    protected $searchFields = [];

    protected $data = [];

    protected $keywords = null;

    protected $sort = null;

    protected $order = self::ORDER_DESC;


    protected function extract($query)
    {
        if (empty($query)) {
            return $this;
        }

        $this->extractQueryPart(preg_replace(self::GROUP_REGEX, '', $query));

        preg_match_all(self::GROUP_REGEX, $query, $groups);

        foreach ($groups[2] as $key => $part) {
            $this->extractQueryPart($part, $key + 1, $groups[1][$key]);
        }

        return $this;
    }

    protected function extractQueryPart($part, $group = 0, $groupmode = self::OPERATOR_AND)
    {
        $params = [];
        preg_match_all(self::FILTER_REGEX, $part, $params);

        foreach ($params[2] as $key => $field) {
            $value = $this->processValue($params[4][$key]);
            $this->addFieldValue($field, $value, $params[3][$key], $params[1][$key], false, $group, $groupmode);
        }

        if ($group == 0) {
            $keywords = trim(preg_replace(self::FILTER_REGEX, '', $part));
            if ($keywords !== '') {
                $this->setKeywords($keywords);
            }
        }
    }

    private function processValue($value)
    {
        $value = str_replace(array('[', ']'), '', $value);

        if ($value == 'null') {
            return null;
        }

        return $value;
    }

    private function unProcessValue($value)
    {
        if (strstr($value, ' ')) {
            $value = '[' . $value . ']';
        } elseif ($value === false) {
            $value = '0';
        } else if ($value === null) {
            $value = 'null';
        }

        return $value;
    }

    /**
     * Add a value to the filter
     *
     * @param string $field
     * @param string $value
     * @param string $comparison see class consts COMPARISON_*
     * @param string $operator see class consts OPERATOR_*
     * @param boolean $default whether the value should be considered a default
     * @param boolean $group which group to apply value to
     * @param string $groupmode the mode for the group
     * @return Filter
     */
    public function addFieldValue($field, $value, $comparison = self::COMPARISON_EQUALS, $operator = self::OPERATOR_AND, $default = false, $group = 0, $groupmode = self::OPERATOR_AND)
    {
        if ($this->hasSearchField($field)) {
            if (isset($this->data[$group]['fields'][$field]) && !$default) {
                foreach ($this->data[$group]['fields'][$field] as $key => $values) {
                    if ($values['default']) {
                        unset($this->data[$group]['fields'][$field][$key]);
                    }
                }
            }

            if (!isset($this->data[$group]['operator'])) {
                $this->data[$group]['operator'] = !empty($groupmode) ? $groupmode : self::OPERATOR_AND;
            }

            $this->data[$group]['fields'][$field][] = [
                'value'      => $value,
                'comparison' => $comparison,
                'default'    => $default,
                'operator'   => !empty($operator) ? $operator : self::OPERATOR_AND
            ];

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

    /**
     * Get all field values, wrapped into groups.
     * Each field contains an array of values
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
     * @param string $field
     * @param int    $group
     * @return array
     */
    public function getFieldValues($field, $group = 0)
    {
        if (isset($this->data[$group]['fields'][$field])) {
            return $this->data[$group]['fields'][$field];
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

        foreach ($this->data as $groupkey => $group) {
            $groupStr = '';
            foreach ($group['fields'] as $field => $values) {
                foreach ($values as $value) {
                    $groupStr.= $field . $value['comparison'] . $this->unProcessValue($value['value']) . ' ';
                }
            }
            if ($groupkey > 0) {
                $groupStr = $group['operator'] . ' (' . trim($groupStr) . ') ';
            }
            $filterStr.= $groupStr;
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
                $operator   = isset($value['operator']) ? $value['operator'] : self::OPERATOR_AND;
            } else {
                $comparison = self::COMPARISON_EQUALS;
                $operator   = self::OPERATOR_AND;
            }
            $this->addFieldValue($field, $value, $comparison, $operator, true);
        }

        return $this;
    }

    public function clear()
    {
        $this->data = [];
        return $this;
    }
}