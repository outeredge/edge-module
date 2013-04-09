<?php

namespace Edge\Service\Exception;

use Edge\Exception\DomainException;

class EntityNotFoundException extends DomainException implements ExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Entity was not found.');
    }
}