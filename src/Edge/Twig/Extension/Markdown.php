<?php

namespace Edge\Twig\Extension;

use Edge\Markdown\MarkdownInterface;
use Twig_Extension;
use Twig_Filter;

class Markdown extends Twig_Extension
{
    /**
     * @var MarkdownInterface
     */
    protected $markdown;

    public function __construct(MarkdownInterface $markdown)
    {
        $this->markdown = $markdown;
    }

    public function getName()
    {
        return 'Markdown';
    }

    public function getFilters()
    {
        return array(
            'markdown' => new Twig_Filter('transform', $this, ['is_safe' => ['html']]),
        );
    }

    /**
     * Transfer plain text to HTML
     *
     * @param string $text
     * @return string
     */
    public function transform($text)
    {
        return $this->markdown->transform($text);
    }
}
