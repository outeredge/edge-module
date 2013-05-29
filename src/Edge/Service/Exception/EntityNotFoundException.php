<?php

namespace Edge\Service\Exception;

use Edge\Exception\DomainException;

class EntityNotFoundException extends DomainException implements ExceptionInterface
{
    protected $code = 404;

    public function __construct()
    {
        parent::__construct('Resource not found');
    }
}