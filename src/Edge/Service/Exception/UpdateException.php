<?php

namespace Edge\Service\Exception;

/**
 * Throw this exception from a "update" method in order to indicate
 * an inability to update an entity
 */
class UpdateException extends EntityErrorsException
{
}