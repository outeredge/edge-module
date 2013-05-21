<?php

namespace Edge\Mail\Api;

use Zebreco\Exception;
use Zend\Crypt\Hmac;

class MailGunMessage extends Message
{
    /**
     * @var string
     */
    protected $apikey;


    public function __construct(array $options = array())
    {
        if (isset($options['api_key'])) {
            $this->apikey = $options['api_key'];
        }
    }

    public function extract($data)
    {
        parent::extract($data);

        if (!$this->hasHeader('signature') || !$this->validate()) {
            throw new Exception\DomainException('Invalid message source.');
        }

        return $this;
    }

    /**
     * Validate the message originated from MailGun
     * {@link http://documentation.mailgun.net/user_manual.html#securing-webhooks}
     *
     * @return boolean
     */
    protected function validate()
    {
        return $this->getHeader('signature') == Hmac::compute($this->apikey, 'sha256', $this->getHeader('timestamp') . $this->getHeader('token'));
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyText()
    {
        if (null === $this->text) {
            $this->text = $this->getHeader('body-plain');
        }
        return $this->text;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml()
    {
        if (null === $this->html) {
            $this->html = $this->getHeader('body-html');
        }
        return $this->html;
    }

    public function getStrippedBodyText()
    {
        return $this->getHeader('stripped-text');
    }

    public function getStrippedBodyHtml()
    {
        return $this->getHeader('stripped-html');
    }

    public function getStrippedSignature()
    {
        return $this->getHeader('stripped-signature');
    }

    public function hasAttachments()
    {
        return $this->hasHeader('attachment-count');
    }

    public function getAttachments()
    {
        $attachments = array();

        if (!$this->hasAttachments()) {
            return $attachments;
        }

        $i = 1;
        while ($i <= $this->getHeader('attachment-count')) {
            $attachments[] = $this->getHeader('attachment-'.$i);
            $i++;
        }

        return $attachments;
    }
}