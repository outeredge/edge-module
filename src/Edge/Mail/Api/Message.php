<?php

namespace Edge\Mail\Api;

use Edge\Mail\Address;
use Edge\Mail\AddressList;
use Edge\Mail\Exception;
use Traversable;
use Zend\Mail\Header\MessageId;
use Zend\Mail\Exception\InvalidArgumentException as MailInvalidArgumentException;

/**
 * This class is designed for use with API based in/out
 * hosted email providers who provided pre-processed message data
 */
class Message implements MessageInterface
{
    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $html;

    /**
     * @var array
     */
    protected $attachments = array();

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var array
     */
    protected $headersKeys = array();

    /**
     * Extract array into message headers
     *
     * @param  array $data
     * @return Message
     */
    public function extract($data)
    {
        if (!is_array($data) && !$data instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expected array or Traversable; received "%s"',
                (is_object($data) ? get_class($data) : gettype($data))
            ));
        }

        if (!empty($this->headers)) {
            $this->clear();
        }

        foreach ($data as $header => $value) {
            $fieldname = ucfirst($this->normalizeFieldName($header));

            $adder  = 'add' . $fieldname;
            $setter = 'set' . $fieldname;

            if (method_exists($this, $adder)) {
                $this->$adder($value);
                continue;
            }

            if (method_exists($this, $setter)) {
                $this->$setter($value);
                continue;
            }

            $this->setHeader($header, $value);
        }

        return $this;
    }

    /**
     * Set (overwrite) From addresses
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressList
     * @param  string|null $name
     * @return Message
     */
    public function setFrom($emailOrAddressList, $name = null)
    {
        $this->removeHeader('from');
        return $this->addFrom($emailOrAddressList, $name);
    }

    /**
     * Add a "From" address
     *
     * @param  string|Address|array|AddressList|Traversable $emailOrAddressOrList
     * @param  string|null $name
     * @return Message
     */
    public function addFrom($emailOrAddressOrList, $name = null)
    {
        $addressList = $this->getFrom();
        $this->updateAddressList($addressList, $emailOrAddressOrList, $name, __METHOD__);
        return $this;
    }

    /**
     * Retrieve list of From senders
     *
     * @return AddressList
     */
    public function getFrom()
    {
        return $this->getAddressListFromHeader('from');
    }

    /**
     * Set (overwrite) To addresses
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressList
     * @param  string|null $name
     * @return Message
     */
    public function setTo($emailOrAddressList, $name = null)
    {
        $this->removeHeader('to');
        return $this->addTo($emailOrAddressList, $name);
    }

    /**
     * Add a "To" address
     *
     * @param  string|Address|array|AddressList|Traversable $emailOrAddressOrList
     * @param  string|null $name
     * @return Message
     */
    public function addTo($emailOrAddressOrList, $name = null)
    {
        $addressList = $this->getTo();
        $this->updateAddressList($addressList, $emailOrAddressOrList, $name, __METHOD__);
        return $this;
    }

    /**
     * Access the address list of the To header, uses Resent-To if set
     *
     * @return AddressList
     */
    public function getTo()
    {
        if ($this->hasHeader('Resent-To')) {
            return $this->getAddressListFromHeader('Resent-To')->merge($this->getAddressListFromHeader('to'));
        }

        return $this->getAddressListFromHeader('to');
    }

    /**
     * Set (overwrite) CC addresses
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressList
     * @param  string|null $name
     * @return Message
     */
    public function setCc($emailOrAddressList, $name = null)
    {
        $this->removeHeader('cc');
        return $this->addCc($emailOrAddressList, $name);
    }

    /**
     * Add a "Cc" address
     *
     * @param  string|Address|array|AddressList|Traversable $emailOrAddressOrList
     * @param  string|null $name
     * @return Message
     */
    public function addCc($emailOrAddressOrList, $name = null)
    {
        $addressList = $this->getCc();
        $this->updateAddressList($addressList, $emailOrAddressOrList, $name, __METHOD__);
        return $this;
    }

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     */
    public function getCc()
    {
        return $this->getAddressListFromHeader('cc');
    }

    /**
     * Set (overwrite) BCC addresses
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressList
     * @param  string|null $name
     * @return Message
     */
    public function setBcc($emailOrAddressList, $name = null)
    {
        $this->removeHeader('bcc');
        return $this->addBcc($emailOrAddressList, $name);
    }

    /**
     * Add a "Bcc" address
     *
     * @param  string|Address|array|AddressList|Traversable $emailOrAddressOrList
     * @param  string|null $name
     * @return Message
     */
    public function addBcc($emailOrAddressOrList, $name = null)
    {
        $addressList = $this->getBcc();
        $this->updateAddressList($addressList, $emailOrAddressOrList, $name, __METHOD__);
        return $this;
    }

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     */
    public function getBcc()
    {
        return $this->getAddressListFromHeader('bcc');
    }

    /**
     * Overwrite the address list in the Reply-To recipients
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressList
     * @param  null|string $name
     * @return Message
     */
    public function setReplyTo($emailOrAddressList, $name = null)
    {
        $this->removeHeader('reply-to');
        return $this->addReplyTo($emailOrAddressList, $name);
    }

    /**
     * Add one or more addresses to the Reply-To recipients
     *
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressOrList
     * @param  null|string $name
     * @return Message
     */
    public function addReplyTo($emailOrAddressOrList, $name = null)
    {
        $addressList = $this->getReplyTo();
        $this->updateAddressList($addressList, $emailOrAddressOrList, $name, __METHOD__);
        return $this;
    }

    /**
     * Access the address list of the Reply-To header
     *
     * @return AddressList
     */
    public function getReplyTo()
    {
        return $this->getAddressListFromHeader('reply-to');
    }

    /**
     * Set Subject header
     *
     * @param string $subject
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->setHeader('subject', $subject);
        return $this;
    }

    /**
     * Get Subject header
     *
     * @return string|false
     */
    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    /**
     * Set a message id
     *
     * @param  string|null $messageId
     * @return Message
     */
    public function setMessageId($messageId = null)
    {
        if (null === $messageId) {
            $messageId = $this->generateMessageId();
        }
        $this->setHeader('message-id', $messageId);
        return $this;
    }

    /**
     * Get messages identifier
     *
     * @return string|null
     */
    public function getMessageId()
    {
        return $this->getHeader('message-id');
    }

    /**
     * Generate a compliant unique Message-Id string
     *
     * @return string
     */
    public function generateMessageId()
    {
        $header = new MessageId();
        return $header->setId()->getId();
    }

    /**
     * Set plain format body text
     *
     * @param  string $text
     * @return Message
     */
    public function setBodyText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Get plain format body text
     *
     * @return string
     */
    public function getBodyText()
    {
        return $this->text;
    }

    /**
     * Set HTML format body text
     *
     * @param  string $html
     * @return Message
     */
    public function setBodyHtml($html)
    {
        $this->html = $html;
        return $this;
    }

    /**
     * Get HTML format body text
     *
     * @return string
     */
    public function getBodyHtml()
    {
        return $this->html;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttachment($filename, $path, $ctype = null, $size = null)
    {
        $this->attachments[] = array(
            'tmp_name' => $path,
            'name'     => $filename,
            'size'     => $size,
            'type'     => $ctype
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachments($attachments)
    {
        $this->attachments = (array) $attachments;
        return $this;
    }

    /**
     * Does a header exist with the specified name?
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        $name = $this->normalizeFieldName($name);
        return in_array($name, $this->headersKeys);
    }

    /**
     * Set a header to the specified value
     *
     * @param string $name
     * @param mixed $value
     * @return Message
     */
    public function setHeader($name, $value)
    {
        $key = $this->normalizeFieldName($name);
        $this->headersKeys[] = $key;
        $this->headers[] = $value;
        return $this;
    }

    /**
     * Get a headers value
     *
     * @param string $name
     * @return null|mixed
     */
    public function getHeader($name)
    {
        $name = $this->normalizeFieldName($name);
        $key  = array_search($name, $this->headersKeys);

        if ($key === false) {
            return null;
        }

        return $this->headers[$key];
    }

    /**
     * Get all header values as key/pair array
     *
     * @return array
     */
    public function getHeaders()
    {
        return array_combine($this->headersKeys, $this->headers);
    }

    /**
     * Remove the specified header from the message
     *
     * @param string $name
     * @return boolean
     */
    public function removeHeader($name)
    {
        $name = $this->normalizeFieldName($name);
        $key  = array_search($name, $this->headersKeys);

        if (!$key) {
            return false;
        }

        unset($this->headersKeys[$key]);
        unset($this->headers[$key]);
        return true;
    }

    /**
     * Normalize a field name
     *
     * @param  string $fieldName
     * @return string
     */
    protected function normalizeFieldName($fieldName)
    {
        return str_replace(array('-', '_', ' ', '.'), '', strtolower($fieldName));
    }

    /**
     * Retrieve the AddressList from a named header
     *
     * Used by default with To, From, Cc, Bcc, and ReplyTo headers. If the header does not
     * exist, instantiates it.
     *
     * @param  string $name
     * @throws Exception\DomainException
     * @return AddressList
     */
    protected function getAddressListFromHeader($name)
    {
        $header = $this->getHeader($name);

        if (empty($header)) {
            $header = new AddressList();
            $this->setHeader($name, $header);
        }

        if (is_string($header)) {
            $header = $this->createAddressList($header);
            $this->setHeader($name, $header);
        }

        if (!$header instanceof AddressList) {
            throw new Exception\DomainException(sprintf(
                'Header of type "%s" is not an AddressList',
                (is_object($header) ? get_class($header) : gettype($header))
            ));
        }

        return $header;
    }

    /**
     * Create an address list from a string
     *
     * @param  string $value
     * @return AddressList
     */
    protected function createAddressList($value)
    {
        $values = str_getcsv($value, ',');
        array_walk(
            $values,
            function (&$value) {
                $value = trim($value);
            }
        );

        $addressList = new AddressList();

        foreach ($values as $address) {
            try {
                $addressList->addFromString($address);
            } catch (MailInvalidArgumentException $ex) {
                continue;
            } catch (Exception\InvalidArgumentException $ex) {
                continue;
            }
        }

        return $addressList;
    }

    /**
     * Update an address list
     *
     * Proxied to this from addFrom, addTo, addCc, addBcc, and addReplyTo.
     *
     * @param  AddressList $addressList
     * @param  string|Address\AddressInterface|array|AddressList|Traversable $emailOrAddressOrList
     * @param  null|string $name
     * @param  string $callingMethod
     * @throws Exception\InvalidArgumentException
     */
    protected function updateAddressList(AddressList $addressList, $emailOrAddressOrList, $name, $callingMethod)
    {
        if ($emailOrAddressOrList instanceof Traversable) {
            foreach ($emailOrAddressOrList as $address) {
                $addressList->add($address);
            }
            return;
        }
        if (is_array($emailOrAddressOrList)) {
            $addressList->addMany($emailOrAddressOrList);
            return;
        }
        if (is_string($emailOrAddressOrList) && $name === null) {
            $addressList->merge($this->createAddressList($emailOrAddressOrList));
            return;
        }
        if (!is_string($emailOrAddressOrList) && !$emailOrAddressOrList instanceof Address\AddressInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string, AddressInterface, array, AddressList, or Traversable as its first argument; received "%s"',
                $callingMethod,
                (is_object($emailOrAddressOrList) ? get_class($emailOrAddressOrList) : gettype($emailOrAddressOrList))
            ));
        }
        $addressList->add($emailOrAddressOrList, $name);
    }

    /**
     * Get all recipients (to's and cc's) of this message as an address list
     *
     * @return \Edge\Mail\AddressList
     */
    public function getAllRecipients()
    {
        $recipients = $this->getTo();
        $ccs        = $this->getCc();

        if (!empty($ccs)) {
            $recipients->merge($ccs);
        }

        return $recipients;
    }

    /**
     * Get an array of message-id's that this message refers to
     *
     * @return array
     */
    public function getReferences()
    {
        $references = array();
        $pattern    = '/(<.*?>)|/';

        if ($this->hasHeader('References')) {
            $references = preg_split($pattern, $this->getHeader('References'), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        }

        if ($this->hasHeader('In-Reply-To')) {
            $references = array_merge($references, preg_split($pattern, $this->getHeader('In-Reply-To'), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
        }

        return array_unique($references);
    }

    protected function clear()
    {
        $this->headers     = array();
        $this->headersKeys = array();
    }
}
