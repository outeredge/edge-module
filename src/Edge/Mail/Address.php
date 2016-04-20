<?php

namespace Edge\Mail;

use Zend\Mail\Address as ZendAddress;
use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Validator\Hostname;

/**
 * Extends Zend\Mail\Address to allow non-strict validation of email addresses
 */
class Address extends ZendAddress
{
    public function __construct($email, $name = null)
    {
        $emailAddressValidator = new EmailAddressValidator(array(
            'allow'  => Hostname::ALLOW_LOCAL,
            'strict' => false
        ));

        if (! is_string($email) || empty($email)) {
            throw new Exception\InvalidArgumentException('Email must be a valid email address');
        }

        if (preg_match("/[\r\n]/", $email)) {
            throw new Exception\InvalidArgumentException('CRLF injection detected');
        }

        if (! $emailAddressValidator->isValid($email)) {
            $invalidMessages = $emailAddressValidator->getMessages();
            throw new Exception\InvalidArgumentException(array_shift($invalidMessages));
        }

        if (null !== $name) {
            if (! is_string($name)) {
                throw new Exception\InvalidArgumentException('Name must be a string');
            }

            if (preg_match("/[\r\n]/", $name)) {
                throw new Exception\InvalidArgumentException('CRLF injection detected');
            }

            $this->name = $name;
        }

        $this->email = $email;
    }
}