<?php

namespace Edge\Mail\Api\Transport;

use Edge\Mail\Exception;
use Zend\Stdlib\AbstractOptions;

class MailGunOptions extends AbstractOptions
{
    protected $apiKey;

    protected $domain;

    protected $testMode = false;

    protected $baseUri = 'https://api.mailgun.net/v2/%s/messages';

    protected $additionalHeaders = array(
        'Message-Id'
    );

    public function setApiKey($key)
    {
        $this->apiKey = $key;
        return $this;
    }

    public function getApiKey()
    {
        if (null === $this->apiKey) {
            throw new Exception\RuntimeException('No API key was specified for the transport');
        }
        return $this->apiKey;
    }

    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;
        return $this;
    }

    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomain()
    {
        if (null === $this->domain) {
            throw new Exception\RuntimeException('No domain was specified for the transport');
        }
        return $this->domain;
    }

    /**
     * Use test mode
     *
     * @param boolean $testMode
     * @return MailGunOptions
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function getTestMode()
    {
        return $this->testMode ? true : false;
    }

    /**
     * An array of additional header names to be appended to the outbound message
     *
     * @param array $headers
     */
    public function setAdditionalHeaders(array $headers)
    {
        $this->additionalHeaders = array_merge($this->additionalHeaders, $headers);
        return $this;
    }

    public function getAdditionalHeaders()
    {
        return $this->additionalHeaders;
    }
}
