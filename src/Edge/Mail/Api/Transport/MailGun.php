<?php

namespace Edge\Mail\Api\Transport;

use Edge\Mail\Api\MessageInterface;
use Edge\Mail\Exception;
use Zend\Mail\AddressList;
use Zend\Http;

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
     * @return string  newly created message identifier (Message-Id) on success
     * @throws Exception\RuntimeException
     */
    public function send(MessageInterface $message)
    {
        $adapter = new Http\Client\Adapter\Curl();
        $adapter->setOptions(array(
            'curloptions' => array(
                CURLOPT_SSL_VERIFYPEER => false, //@todo avoid this
            )
        ));

        $client  = new Http\Client();
        $client->setAdapter($adapter);
        $client->setMethod('POST');
        $client->setUri(sprintf($this->getOptions()->getBaseUri(), $this->getOptions()->getDomain()));
        $client->setParameterPost($this->messageToPost($message));
        $client->setAuth('api', $this->getOptions()->getApiKey());

        $response = $client->send();
        $result   = $response->getContent();

        if ($response->getHeaders()->get('Content-Type')->getFieldValue() == 'application/json') {
            $result = json_decode($result, true);
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
     * Convert a Message object into a suitable array for POSTing
     *
     * @param MessageInterface $message
     */
    public function messageToPost(MessageInterface $message)
    {
        $data = array(
            'from'    => $message->hasHeader('from') ? $this->addressListToString($message->getFrom()) : null,
            'to'      => $message->hasHeader('to') ? $this->addressListToString($message->getTo()) : null,
            'cc'      => $message->hasHeader('cc') ? $this->addressListToString($message->getCc()) : null,
            'bcc'     => $message->hasHeader('bcc') ? $this->addressListToString($message->getBcc()) : null,
            'subject' => $message->getSubject(),
            'text'    => $message->getBodyText(),
            'html'    => $message->getBodyHtml(),
        );

        foreach ($this->getOptions()->getAdditionalHeaders() as $name) {
            if ($message->hasHeader($name)) {
                $data['h:'.$name] = $message->getHeader($name);
            }
        }

        if ($this->getOptions()->getTestMode()) {
            $data['o:testmode'] = 'yes';
        }

        return $data;
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
                if (false !== strstr($name, ',')) {
                    $name = sprintf('"%s"', $name);
                }
                $emails[] = sprintf('%s <%s>', $name, $email);
            }
        }

        if (empty($emails)) {
            return null;
        }

        return implode(', ', $emails);
    }
}