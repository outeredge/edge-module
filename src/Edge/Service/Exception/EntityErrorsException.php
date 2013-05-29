<?php

namespace Edge\Service\Exception;

use PhlyRestfully\Exception\DomainException;

class EntityErrorsException extends DomainException implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     * @param array $errors [optional]
     */
    public function __construct($message = null, $code = null, Exception $previous = null, array $errors = null)
    {
        if (!empty($errors)) {
            $this->setAdditionalDetails(array('errors' => $errors));
        }

        parent::__construct($message, $code, $previous);
    }
}