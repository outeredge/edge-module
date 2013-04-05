<?php

namespace Edge\Service\Exception;

use Edge\Exception\DomainException;

/**
 * This exception can hold an array of errors for an entity for later formatting
 */
class EntityErrorsException extends DomainException implements ExceptionInterface
{
    protected $errors;

    /**
     * {@inheritdoc}
     * @param array $errors [optional]
     */
    public function __construct($message, $code = 0, Exception $previous = null, array $errors = null)
    {
        if ($errors) {
            $this->setErrors($errors);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Set entity error messages
     *
     * @param array $error
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get entity error messages
     *
     * @return array|null
     */
    public function getErrors()
    {
        return $this->errors;
    }
}