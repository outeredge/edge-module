<?php

namespace Edge\Doctrine\Search;

use Zend\Stdlib\AbstractOptions;

class DoctrineSearcherOptions extends AbstractOptions
{
    protected $__strictMode__ = false;

    protected $fieldMappings = array();

    protected $keywordFields = array();

    protected $joinTables = array();

    protected $joinConditionals = array();

    public function setFieldMappings(array $fields)
    {
        $this->fieldMappings = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

    public function setKeywordFields(array $fields)
    {
        $this->keywordFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getKeywordFields()
    {
        return $this->keywordFields;
    }

    public function setJoinTables(array $joins)
    {
        $this->joinTables = $joins;
        return $this;
    }

    /**
     * @return array
     */
    public function getJoinTables()
    {
        return $this->joinTables;
    }

    public function setJoinConditionals(array $joins)
    {
        $this->joinConditionals = $joins;
        return $this;
    }

    /**
     * @return array
     */
    public function getJoinConditionals()
    {
        return $this->joinConditionals;
    }
}