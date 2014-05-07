<?php

namespace Edge\Mail\Api;

use Edge\Mail\Exception;
use Edge\Stdlib\StreamUtils;
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

    public function extract($data, $validate = true)
    {
        parent::extract($data);

        if ($validate && !$this->validate()) {
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
        if (!$this->hasHeader('signature')) {
            return false;
        }

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

    public function getBodyMime()
    {
        if (!$this->hasHeader('body-mime')) {
            throw new Exception\DomainException('No MIME content available on message');
        }
        return $this->getHeader('body-mime');
    }

    public function setBodyMime($mime)
    {
        $this->setHeader('body-mime', $mime);
        return $this;
    }

    /**
     * Get a stripped version of the body without quoted parts, where possible
     *
     * @return string
     */
    public function getStrippedBody()
    {
        $text      = $this->getStrippedBodyText();
        $signature = $this->getStrippedSignature();

        if (!empty($signature)) {
            $text.= "\n" . $signature;
        }

        if (empty($text)) {
            $text = $this->getBodyText();
        }

        if (empty($text)) {
            $text = 'No message content';
        }

        return $text;
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

    /**
     * Does this message contain attachments
     *
     * @return boolean
     */
    public function hasAttachments()
    {
        return $this->hasHeader('attachment-count') || !empty($this->attachments);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachments()
    {
        if (!$this->hasHeader('attachment-count')) {
            return parent::getAttachments();
        }

        $attachments = array();

        $i = 1;
        while ($i <= $this->getHeader('attachment-count')) {
            if ($this->hasHeader('attachment-'.$i)) {
                $attachments[] = $this->getHeader('attachment-'.$i);
            }
            $i++;
        }

        return $attachments;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachments($attachments)
    {
        $this->attachments = array();

        if (is_array($attachments)) {
            return parent::setAttachments($attachments);
        }

        foreach (json_decode($attachments, true) as $attachment) {
            $tmp = tempnam(sys_get_temp_dir(), 'attach_');
            $url = str_replace('://', '://api:' . $this->apikey . '@', $attachment['url']);

            $error = UPLOAD_ERR_NO_FILE;
            if (file_put_contents($tmp, StreamUtils::file_get_contents($url))) {
                $error = UPLOAD_ERR_OK;
            }

            $this->attachments[] = array(
                'tmp_name' => $tmp,
                'name'     => $attachment['name'],
                'size'     => $attachment['size'],
                'type'     => $attachment['content-type'],
                'error'    => $error
            );
        }

        return $this;
    }


    public function getMessageUrl()
    {
        return $this->getHeader('message-url');
    }
}