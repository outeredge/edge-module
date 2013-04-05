<?php

namespace Edge\Service\Exception;

/**
 * Throw this exception from a "create" method in order to indicate
 * an inability to create an entity
 */
class CreationException extends EntityErrorsException
{
}