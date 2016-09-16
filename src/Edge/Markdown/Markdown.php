<?php

namespace Edge\Markdown;

use Parsedown;

class Markdown extends Parsedown implements MarkdownInterface
{
    public function __construct($breaksEnabled = true, $urlsLinked = true, $markupEscaped = true)
    {
        $this->setBreaksEnabled($breaksEnabled);
        $this->setUrlsLinked($urlsLinked);
        $this->setMarkupEscaped($markupEscaped);
    }

    /**
     * Apply markdown to plain text
     *
     * @param string $text
     * @return string
     */
    public function transform($text)
    {
        return $this->text($text);
    }
}