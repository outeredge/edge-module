<?php

namespace Edge\Mail\Storage;

use PHPUnit_Framework_TestCase;

class MessageTest extends PHPUnit_Framework_TestCase
{
    protected $file;

    public function setUp()
    {
        $this->file = __DIR__ . '/_files/mail.txt';
    }

    public function testReplyToTakesPriority()
    {
        $message = new Message(array('file' => $this->file));
        $this->assertEquals('david@zebreco.com', $message->getFrom()->current()->getEmail());
    }

    public function testBodyReturnsPlainTextPartOnly()
    {
        $message = new Message(array('file' => $this->file));
        $this->assertEquals('Test', $message->getBody());
    }

}