<?php

namespace Edge\Mail\Api\Transport;

use Edge\Mail\Api\MailGunMessage;
use Edge\Mail\Api\MessageInterface;
use Edge\Mail\Exception;
use Edge\Stdlib\StreamUtils;
use Zend\Mail\AddressList;
use Zend\Http;
use Zend\Json\Json;

class MailGun implements TransportInterface
{
    /**
     * @var MailGunOptions
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param  MailGunOptions $options Optional
     */
    public function __construct(MailGunOptions $options = null)
    {
        if (!$options instanceof MailGunOptions) {
            $options = new MailGunOptions();
        }
        $this->setOptions($options);
    }

    /**
     * Set options
     *
     * @param  MailGunOptions $options
     * @return MailGun
     */
    public function setOptions(MailGunOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get options
     *
     * @return MailGunOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Send a message via MailGun
     *
     * @param MessageInterface $message
     * @return string newly created message identifier (Message-Id) on success
     * @throws Exception\RuntimeException
     */
    public function send(MessageInterface $message)
    {
        $client = $this->getHttpClient();
        $data   = $this->messageToPost($message);
        $uri    = sprintf($this->getOptions()->getBaseUri(), $this->getOptions()->getDomain());

        if (!empty($data['message'])) {
            $client->setFileUpload('message.mime', 'message', $data['message'], 'message/rfc2822');
            unset($data['message']);
            $uri.= '.mime';
        }

        $client->setMethod('POST');
        $client->setUri($uri);
        $client->setParameterPost($data);

        foreach ($message->getAttachments() as $attachment) {
            $client->setFileUpload($attachment['name'], 'attachment', StreamUtils::file_get_contents($attachment['tmp_name']), $attachment['type']);
        }

        $response = $client->send();
        $result   = $response->getBody();

        if ($response->getHeaders()->get('Content-Type')->getFieldValue() == 'application/json') {
            $result = Json::decode($result, Json::TYPE_ARRAY);
        }

        if (!$response->isSuccess()) {
            $errorstr = $response->getReasonPhrase();
            if (is_array($result) && !empty($result['message'])) {
                $errorstr = $result['message'];
            }
            throw new Exception\RuntimeException('Unable to send mail: ' . $errorstr);
        }

        if (!is_array($result) || empty($result['id'])) {
            throw new Exception\RuntimeException('Unexpected response from API');
        }

        return $result['id'];
    }

    /**
     * Retrieve full message from Messages API
     *
     * @param MailGunMessage|string $messageOrUrl
     * @param bool $mime
     * @return MailGunMessage
     * @throws Exception\RuntimeException
     */
    public function retrieve($messageOrUrl, $mime = false)
    {
        if (!$messageOrUrl instanceof MailGunMessage) {
            $messageOrUrl = $this->createMessage()->setHeader('message-url', $messageOrUrl);
        }

        $client = $this->getHttpClient();
        $client->setUri($messageOrUrl->getMessageUrl());

        if ($mime) {
            $client->setHeaders(array('Accept' => 'message/rfc2822'));
        }

        $response = $client->send();
        $result   = $response->getBody();

        if (stripos($response->getHeaders()->get('Content-Type')->getFieldValue(), 'application/json') === 0) {
            $result = Json::decode($result, Json::TYPE_ARRAY);
        }

        if (!$response->isSuccess()) {
            $errorstr = $response->getReasonPhrase();
            if (is_array($result) && !empty($result['message'])) {
                $errorstr = $result['message'];
            }
            throw new Exception\RuntimeException('Unable to retrieve message: ' . $errorstr);
        }

        if (!is_array($result)) {
            throw new Exception\RuntimeException('Unexpected response from API');
        }

        return $messageOrUrl->extract($result, false);
    }

    /**
     * Create a new message for use with this transport
     *
     * @return MailGunMessage
     */
    public function createMessage()
    {
        return new MailGunMessage(array('api_key' => $this->getOptions()->getApiKey()));
    }

    /**
     * Convert a Message object into a suitable array for POSTing
     *
     * @param MessageInterface $message
     */
    protected function messageToPost(MessageInterface $message)
    {
        $data = array(
            'from'    => $message->hasHeader('from') ? $this->addressListToString($message->getFrom()) : null,
            'to'      => $message->hasHeader('to') ? $this->addressListToString($message->getTo()) : null,
            'cc'      => $message->hasHeader('cc') ? $this->addressListToString($message->getCc()) : null,
            'bcc'     => $message->hasHeader('bcc') ? $this->addressListToString($message->getBcc()) : null,
            'subject' => $message->getSubject(),
            'text'    => $message->getBodyText(),
            'html'    => $message->getBodyHtml(),
            'message' => $message->getHeader('body-mime')
        );

        if (empty($data['text']) && empty($data['html']) && empty($data['message'])) {
            $data['text'] = ' ';
        }

        foreach ($this->getOptions()->getAdditionalHeaders() as $name) {
            if ($message->hasHeader($name)) {
                $value = $message->getHeader($name);
                $data['h:'.$name] = ($value instanceof AddressList) ? $this->addressListToString($value) : $value;
            }
        }

        if ($this->getOptions()->getTestMode()) {
            $data['o:testmode'] = 'yes';
        }

        return array_filter($data);
    }

    /**
     * @return Http\Client
     */
    protected function getHttpClient()
    {
        $client  = new Http\Client();
        $client->setOptions(array(
            'maxredirects'  => 0,
            'timeout'       => 60,
            'sslverifypeer' => false //@todo avoid this
        ));
        $client->setAdapter(new Http\Client\Adapter\Curl);
        $client->setAuth('api', $this->getOptions()->getApiKey());

        return $client;
    }

    /**
     * Convert an address to a string
     *
     * @param AddressList $addressList
     * @return string|null
     */
    protected function addressListToString(AddressList $addressList)
    {
        $emails   = array();
        foreach ($addressList as $address) {
            $email = $address->getEmail();
            $name  = $address->getName();
            if (empty($name)) {
                $emails[] = $email;
            } else {
                $emails[] = sprintf('"%s" <%s>', $name, $email);
            }
        }

        if (empty($emails)) {
            return null;
        }

        return implode(', ', $emails);
    }
}
