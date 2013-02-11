<?php
/**
* Google style search
*  - Wrap parameters in brackets if is more than one word
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

    const COMPARISON_EQUALS     = ':';
    const COMPARISON_NOT_EQUALS = '!';
    const COMPARISON_LIKE       = '~';

    const QUERY_REGEX = '/([a-zA-Z-\.]+)(:|!|~)((?:\([^)]+?\)|[^( ]+))/';

    /**
     * Allowed search fields
     * @var array
     */
    protected $validSearchFields = array();

    protected $replaceValues = array();

    protected $data = array();

    protected $keywords = null;

    protected $sort = null;

    protected $order = self::ORDER_DESC;


    protected function extract($query)
    {
        if (empty($this->validSearchFields)) {
            throw new \Exception('No search fields were specified');
        }

        $keywords = trim(str_replace('  ', ' ', preg_replace(self::QUERY_REGEX, '', $query)));
        if ($keywords !== '') {
            $this->keywords = $keywords;
        }

        $params = array();
        preg_match_all(self::QUERY_REGEX, $query, $params);
        foreach ($params[1] as $key => $field) {
            $value = $this->processValue($params[3][$key]);

            if (isset($this->validSearchFields[$field])) {
                $this->setFieldValue($field, $value, $params[2][$key]);
                continue;
            }

            if ($field == self::PARAM_SORT) {
                if (isset($this->validSearchFields[$value])) {
                    $this->sort = $value;
                }
                continue;
            }

            if ($field == self::PARAM_ORDER) {
                if ($value != self::ORDER_DESC) {
                    $this->order = self::ORDER_ASC;
                }
                continue;
            }
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

    public function setFieldValue($field, $value, $comparison = self::COMPARISON_EQUALS)
    {
        if (isset($this->validSearchFields[$field])) {
            $value = $this->replaceValue($field, $value);
            $this->data[$field] = array(
                'value'      => $value,
                'comparison' => $comparison,
            );
        } else {
            throw new \Exception("Invalid field [$field] specified");
        }
        return $this;
    }

    public function getFieldValue($field)
    {
        if (isset($this->data[$field])) {
            return $this->data[$field];
        }
        return null;
    }

    public function getAllFieldValues()
    {
        return $this->data;
    }

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

        foreach ($this->data as $field => $value) {
            $filterStr.= $field . $value['comparison'] . $value['value'] . ' ';
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
        if (!array_key_exists($sort, $this->validSearchFields)) {
            throw new \Exception("Invalid sort field [$sort] specified");
        }

        if ($order !== self::ORDER_DESC) {
            $order = self::ORDER_ASC;
        }

        $this->sort  = $sort;
        $this->order = $order;

        return $this;
    }

    public function setValidSearchFields(array $fields)
    {
        if (ArrayUtils::isList($fields)) {
            $this->validSearchFields = array_flip($fields);
        } else {
            $this->validSearchFields = $fields;
        }
        return $this;
    }

    public function getValidSearchFields()
    {
        return $this->validSearchFields;
    }

    public function setDefaultValues(array $values)
    {
        foreach ($values as $field => $value) {
            $comparison = self::COMPARISON_EQUALS;
            if (is_array($value)) {
                $value = $value['value'];
                $comparison = isset($value['comparison']) ? $value['comparison'] : $comparison;
            }
            $this->setFieldValue($field, $value, $comparison);
        }

        return $this;
    }

    /**
     * Set replace values
     *
     * @param array $values An array of fields containing key value pair arrays,
     *                      where the key equals the expected string and the value
     *                      equals the replacement string
     * @return Filter
    */
    public function setReplaceValues(array $values)
    {
        $this->replaceValues = $values;
        return $this;
    }

    public function getReplaceValues()
    {
        return $this->replaceValues;
    }

    protected function replaceValue($field, $value)
    {
        if (isset($this->replaceValues[$field]) && is_array($this->replaceValues[$field])) {
            foreach($this->replaceValues[$field] as $old => $new) {
                if ($value == $old) {
                    $value = $new;
                    break;
                }
            }
        }
        return $value;
    }

    public function clear()
    {
        $this->data = array();
        return $this;
    }
}