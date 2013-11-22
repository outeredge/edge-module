<?php

namespace Edge\Mail\Api;

use PHPUnit_Framework_TestCase;

class MailGunMessageTest extends PHPUnit_Framework_TestCase
{
    protected $message;

    public function setUp()
    {
        $this->message = new MailGunMessage(array('api_key' => 'test'));
    }

    public function tearDown()
    {
        $this->message = null;
    }

    protected function getValidMessageArray()
    {
        return array(
            'signature' => '27881e572b4cb814042913031fd64b3a4ffabcd92977a525dee039643f943882',
            'timestamp' => 1379066739,
        );
    }

    public function testGetStrippedBody()
    {
        $data = array_merge(
            $this->getValidMessageArray(),
            array(
                'stripped-text' => 'My stripped text',
            )
        );

        $this->message->extract($data);

        $this->assertEquals("My stripped text", $this->message->getStrippedBody());
    }

    public function testGetStrippedBodyWithSignature()
    {
        $data = array_merge(
            $this->getValidMessageArray(),
            array(
                'stripped-text'      => 'My stripped text',
                'stripped-signature' => 'Thanks, David'
            )
        );

        $this->message->extract($data);

        $this->assertEquals("My stripped text\nThanks, David", $this->message->getStrippedBody());
    }

    public function testGetPlainBodyWhenNoStrippedBody()
    {
        $data = array_merge(
            $this->getValidMessageArray(),
            array(
                'stripped-text' => '',
                'body-plain'    => 'My plain body'
            )
        );

        $this->message->extract($data);

        $this->assertEquals("My plain body", $this->message->getStrippedBody());
    }

    public function testNoContentMessageReturnedWhenAllFieldsEmpty()
    {
        $data = array_merge(
            $this->getValidMessageArray(),
            array(
                'stripped-text'      => '',
                'stripped-signature' => '',
                'body-plain'         => ''
            )
        );

        $this->message->extract($data);

        $this->assertEquals("No message content", $this->message->getStrippedBody());
    }

    public function testNoContentMessageReturnedWhenAllFieldsNotSet()
    {
        $this->message->extract($this->getValidMessageArray());

        $this->assertEquals("No message content", $this->message->getStrippedBody());
    }
}