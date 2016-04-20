<?php

namespace Edge\Mail;

use Edge\Mail\Address as Address;
use Zend\Mail\AddressList as ZendAddressList;

class AddressList extends ZendAddressList
{
    protected function createAddress($email, $name)
    {
        return new Address($email, $name);
    }
}