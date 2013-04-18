<?php

namespace Edge\Search;

interface ConverterInterface
{
    /**
     * @param  mixed $data
     * @return mixed
     */
    public function convert($data);
}