<?php

namespace Edge\Markdown;

use Michelf\MarkdownExtra;

class Markdown implements MarkdownInterface
{
    /**
     * Apply Markdown to (already escaped) plain text
     *
     * @param string $text
     * @return string
     */
    public function transform($text)
    {
        $markdown = new MarkdownExtra();
        $markdown->hard_wrap = true;
        $markdown->code_block_content_func = function($input) {
            return $input;
        };
        $markdown->code_span_content_func = function($input) {
            return $input;
        };

        return $markdown->transform($this->applyFlavour($text));
    }
}
