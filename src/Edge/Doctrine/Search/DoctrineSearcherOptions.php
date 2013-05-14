<?php

namespace Edge\Doctrine\Search;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Zend\Stdlib\AbstractOptions;

class DoctrineSearcherOptions extends AbstractOptions
{
    protected $fieldMappings = array();

    protected $keywordFields = array();

    protected $joinTables = array();

    protected $metadata;


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

    /**
     * @param ClassMetadata $metadata
     */
    public function setMetaData(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return ClassMetadata
     */
    public function getMetaData()
    {
        return $this->metadata;
    }
}