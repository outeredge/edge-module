<?php

namespace Edge\Service\Exception;

use Edge\Exception\DomainException;

/**
 * Throw this exception from a "delete" method in order to indicate
 * an inability to delete an entity
 */
class DeleteException extends DomainException
{
}