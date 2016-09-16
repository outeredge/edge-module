<?php

namespace Edge\Markdown\View\Helper;

use Edge\Markdown\MarkdownInterface;
use Zend\View\Helper\AbstractHelper;

class Markdown extends AbstractHelper
{
    protected $markdown;

    public function __construct(MarkdownInterface $markdown)
    {
        $this->markdown = $markdown;
    }

    public function __invoke($text)
    {
        return $this->markdown->transform($text);
    }
}