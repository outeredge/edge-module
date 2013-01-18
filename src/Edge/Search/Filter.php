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

    const QUERY_REGEX = '/([a-zA-Z\-]+)(:|!)((?:\([^)]+?\)|[^( ]+))/';

    /**
     * Allowed search fields
     * @var array
     */
    protected $validSearchFields = array();

    /**
     * Array of field names and their default values
     * @var array
     */
    protected $defaultValues = array();

    protected $data = array();

    protected $keywords;

    protected $sort;

    protected $order = self::ORDER_DESC;


    protected function extract($query)
    {
        if (empty($this->validSearchFields)) {
            throw new \Exception('No search fields were specified');
        }

        $this->data = $this->defaultValues;

        $keywords = trim(str_replace('  ', ' ', preg_replace(self::QUERY_REGEX, '', $query)));
        if ($keywords !== '') {
            $this->keywords = $keywords;
        }

        $params = array();
        preg_match_all(self::QUERY_REGEX, $query, $params);
        foreach ($params[1] as $key => $field) {
            $value  = str_replace(array('(', ')'), '', $params[3][$key]);

            if (isset($this->validSearchFields[$field])) {
                $equals = $params[2][$key] == ':' ? true : false;

                if ($value == 'null') {
                    $value = null;
                }

                if (strstr($value, ',')) {
                    $value = explode(',', $value);
                }

                $this->data[$field] = array(
                    'value'  => $value,
                    'equals' => $equals,
                );
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

    public function setFieldValue($field, $value, $equals = true)
    {
        if (array_key_exists($field, $this->validSearchFields)) {
            $this->data[$field] = array(
                'value'  => $value,
                'equals' => $equals,
            );
        } else {
            throw new \Exception('Invalid field specified');
        }
        return $this;
    }

    public function getFieldValue($field)
    {
        if (array_key_exists($field, $this->data)) {
            return $this->data[$field];
        }

        if (array_key_exists($field, $this->defaultValues)) {
            return $this->defaultValues[$field];
        }

        return null;
    }

    public function getAllFieldValues()
    {
        return array_merge($this->defaultValues, $this->data);
    }

    public function setQueryString($filter)
    {
        $this->extract($filter);
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getQueryString()
    {
        if (empty($this->data)) {
            return;
        }

        $filterStr = '';
        foreach ($this->data as $field => $value) {
            $filterStr.= $field . ($value['equals']?':':'!') . $value['value'] . ' ';
        }

        if (null !== $this->sort) {
            $filterStr.= self::PARAM_SORT . ':' . $this->sort . ' ' . self::PARAM_ORDER . ':' . $this->order . ' ';
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
            throw new \Exception('Invalid field specified');
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
        foreach ($values as $key => $value) {
            if (!is_array($value)) {
                $value = array(
                    'value' => $value,
                    'equals' => true
                );
            }
            $this->defaultValues[$key] = $value;
        }
        return $this;
    }
}