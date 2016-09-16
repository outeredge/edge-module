<?php

namespace Edge\Markdown\View\Helper;

use Edge\Markdown\Markdown as EdgeMarkdown;
use Zend\View\Helper\AbstractHelper;

class Markdown extends AbstractHelper
{
    protected $markdown;

    public function __invoke($text)
    {
        return $this->getMarkdown()->transform($text);
    }

    protected function getMarkdown()
    {
        if ($this->markdown === null) {
            $this->markdown = new EdgeMarkdown();
        }
        return $this->markdown;
    }
}