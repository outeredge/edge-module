<?php

namespace Edge\Mail\Api\Transport;

use Edge\Mail\Api\MessageInterface;

interface TransportInterface
{
    /**
     * Send a message
     *
     * @param \Edge\Mail\Api\MessageInterface $message
     * @return string  newly created messages identifier on success
     * @throws \Edge\Mail\Exception\RuntimeException  on error
     */
    public function send(MessageInterface $message);
}
