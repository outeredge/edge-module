<?php

namespace Edge\Mail\Api;

use Zend\Mail\AddressList;

interface MessageInterface
{
    /**
     * Extract raw format into message and headers
     *
     * @param  mixed $data
     * @return MessageInterface
     */
    public function extract($data);

    /**
     * Set (overwrite) From addresses
     *
     * @param  mixed $emailOrAddressList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function setFrom($emailOrAddressList, $name = null);

    /**
     * Add a "From" address
     *
     * @param  mixed $emailOrAddressOrList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function addFrom($emailOrAddressOrList, $name = null);

    /**
     * Retrieve list of From senders
     *
     * @return AddressList
     */
    public function getFrom();

    /**
     * Set (overwrite) To addresses
     *
     * @param  mixed $emailOrAddressList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function setTo($emailOrAddressList, $name = null);

    /**
     * Add a "To" address
     *
     * @param  mixed $emailOrAddressOrList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function addTo($emailOrAddressOrList, $name = null);

    /**
     * Access the address list of the To header
     *
     * @return AddressList
     */
    public function getTo();

    /**
     * Set (overwrite) CC addresses
     *
     * @param  mixed $emailOrAddressList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function setCc($emailOrAddressList, $name = null);

    /**
     * Add a "Cc" address
     *
     * @param  mixed $emailOrAddressOrList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function addCc($emailOrAddressOrList, $name = null);

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     */
    public function getCc();

    /**
     * Set (overwrite) BCC addresses
     *
     * @param  mixed $emailOrAddressList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function setBcc($emailOrAddressList, $name = null);

    /**
     * Add a "Bcc" address
     *
     * @param  mixed $emailOrAddressOrList
     * @param  string|null $name
     * @return MessageInterface
     */
    public function addBcc($emailOrAddressOrList, $name = null);

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     */
    public function getBcc();

    /**
     * Overwrite the address list in the Reply-To recipients
     *
     * @param  mixed $emailOrAddressList
     * @param  null|string $name
     * @return MessageInterface
     */
    public function setReplyTo($emailOrAddressList, $name = null);

    /**
     * Add one or more addresses to the Reply-To recipients
     *
     * @param  mixed $emailOrAddressOrList
     * @param  null|string $name
     * @return MessageInterface
     */
    public function addReplyTo($emailOrAddressOrList, $name = null);

    /**
     * Access the address list of the Reply-To header
     *
     * @return AddressList
     */
    public function getReplyTo();

    /**
     * Set Subject header
     *
     * @param string $subject
     * @return MessageInterface
     */
    public function setSubject($subject);

    /**
     * Get Subject header
     *
     * @return string|false
     */
    public function getSubject();

    /**
     * Set a message id, should auto generate when null
     *
     * @param  string|null $messageId
     * @return MessageInterface
     */
    public function setMessageId($messageId = null);

    /**
     * Get messages identifier
     *
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set plain format body text
     *
     * @param  string $text
     * @return MessageInterface
     */
    public function setBodyText($text);

    /**
     * Get plain format body text
     *
     * @return string
     */
    public function getBodyText();

    /**
     * Set HTML format body text
     *
     * @param  string $html
     * @return MessageInterface
     */
    public function setBodyHtml($html);

    /**
     * Get HTML format body text
     *
     * @return string
     */
    public function getBodyHtml();

    /**
     * Add a new attachment
     *
     * @param string $filename
     * @param string $path
     * @param string|null $ctype
     * @param int|null $size
     * @return MessageInterface
     */
    public function addAttachment($filename, $path, $ctype = null, $size = null);

    /**
     * Are there attachments?
     *
     * @return bool
     */
    public function hasAttachments();

    /**
     * Set multiple attachments
     *
     * @param mixed $attachments
     * @return MessageInterface
     */
    public function setAttachments($attachments);

    /**
     * Get array of attachments in $_FILES format
     *
     * @return array
     */
    public function getAttachments();

    /**
     * Does a header exist with the specified name?
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader($name);

    /**
     * Set a header to the specified value
     *
     * @param string $name
     * @param mixed $value
     * @return MessageInterface
     */
    public function setHeader($name, $value);

    /**
     * Get a headers value
     *
     * @param string $name
     * @return false|mixed
     */
    public function getHeader($name);

    /**
     * Get all header values as key/pair array
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Remove the specified header from the message
     *
     * @param string $name
     * @return boolean
     */
    public function removeHeader($name);
}