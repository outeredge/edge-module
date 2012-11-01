<?php

namespace Edge\Mail\Storage;

use Zend\Mail\Storage\Message as StorageMessage;
use Zend\Mail\Storage\Part;
use Zend\Filter\StripTags;

class Message extends StorageMessage {

    protected $attachments = array();

    protected $body = null;

    protected $allowedTags = '<b><i><em><strong><u><br><p><br/>';

    public function __construct(array $params)
    {
        parent::__construct($params);

        if (isset($params['allowedtags'])) {
            $this->allowedTags = $params['allowedtags'];
        }

        $this->processParts($this);
    }

    protected function processParts(Part $part, $multiType = null)
    {
        if ($part->isMultiPart()) {
            foreach ($part as $subPart) {
                try {
                    $multiType = strtok($part->contentType, ';');
                    $this->processParts($subPart, $multiType);
                } catch (\Exception $ex) {
                    // ignore in-case of part extraction failure
                }
            }
        } else {
            $this->extractPart($part, $multiType);
        }
    }

    protected function extractPart(Part $part, $multiType = null)
    {
        $mimeType = strtok($part->contentType, ';');
        $encoding = isset($part->contentTransferEncoding) ? $part->contentTransferEncoding : null;

        preg_match('/name="(?<filename>[a-zA-Z0-9.\-_ ]+)"/is', $part->contentType, $attachmentName);

        if (isset($attachmentName['filename'])) {
            $this->attachments[] = array(
                'mimetype' => $mimeType,
                'filename' => $attachmentName['filename'],
                'data'     => $this->decodeContent($part->getContent(), $encoding, false)
            );
            return;
        }

        if ($mimeType != 'text/plain' && $mimeType != 'text/html') {
            return;
        }

        if ($multiType != 'multipart/alternative' || null === $this->body) {

            $this->body .= $this->decodeContent($part->getContent(), $encoding);
        }
    }

    protected function decodeContent($content, $transferEncoding = null, $stripTags = true)
    {
        switch ($transferEncoding) {
            case 'base64':
                $content = base64_decode($content);
                break;
            case 'quoted-printable':
                $content = quoted_printable_decode($content);
                break;
        }

        if ($stripTags) {
            $filter = new StripTags(array('allowTags' => $this->allowedTags));
            $content = $filter->filter($content);
        }

        return $content;
    }

    public function getBody()
    {
        return $this->_stripBreaklines($this->body);
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Get address list of froms
     *
     * @return \Zend\Mail\AddressList
     */
    public function getFrom()
    {
        if (isset($this->replyTo)) {
            return $this->getHeader('reply-to')->getAddressList();
        }

        return $this->getHeader('from')->getAddressList();
    }

    /**
     * Get address list of to's
     *
     * @return \Zend\Mail\AddressList
     */
    public function getTo()
    {
        $return = $this->getHeader('to')->getAddressList();
        if (isset($this->resentTo)) {
            $return = $this->getHeader('resent-to')->getAddressList()->merge($return);
        }
        return $return;
    }

    public function getCc()
    {
        if (!isset($this->cc)) {
            return null;
        }

        return $this->getHeader('cc')->getAddressList();
    }

    public function getAllContacts()
    {
        $contacts = $this->getTo();

        if (null !== $cc = $this->getCc()) {
            $contacts->merge($cc);
        }

        return $contacts;
    }

    public function getReferences()
    {
        $messageIds = array();
        $pattern = '/(<.*?>)|/';

        if (isset($this->references)) {
            $messageIds = preg_split($pattern, $this->references, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        }

        if (isset($this->inReplyTo)) {
            $messageIds = array_merge($messageIds, preg_split($pattern, $this->inReplyTo, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
        }

        return array_unique($messageIds);
    }

    protected function _stripBreaklines($content)
    {
        //@todo add more line breaks, found some here http://stackoverflow.com/questions/278788/parse-email-content-from-quoted-reply
        $breaklines = array(
            '/on(.)*wrote:/im',
            '/On [a-zA-Z0-9\s\+\.,:@"\'\<\>]* wrote:/i',
            '-----Original Message-----',
            '/[a-zA-Z0-9\s\+\.,:@"\'\<\>]*wrote \.\./i'
        );

        // parse body through regex to remove everything below the breaklines
        foreach ($breaklines as $val) {
            if (substr($val, 0, 1) == '/') {
                $breakcontent = preg_match($val, $content, $matches, PREG_OFFSET_CAPTURE) ? substr($content, 0, $matches[0][1]) : false;
            } else {
                $breakcontent = strchr($content, $val, true);
            }
            if ($breakcontent) {
                return $breakcontent;
            }
        }

        return trim(rtrim($content));
    }
}
