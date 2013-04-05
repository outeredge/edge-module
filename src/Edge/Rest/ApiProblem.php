<?php

namespace Edge\Rest;

use PhlyRestfully\ApiProblem as PhlyApiProblem;

class ApiProblem extends PhlyApiProblem
{
    protected $additional = array();

    /**
     * {@inheritdoc}
     *
     * @param array $additional additional fields [optional]
     */
    public function __construct($httpStatus, $detail, $describedBy = null, $title = null, array $additional = array())
    {
        $this->additional = $additional;
        parent::__construct($httpStatus, $detail, $describedBy, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), $this->additional);
    }
}