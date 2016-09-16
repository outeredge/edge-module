<?php

namespace Edge\Twig\Extension;

use Edge\Markdown\Markdown as EdgeMarkdown;
use Edge\Markdown\MarkdownInterface;
use Twig_Extension;
use Twig_Filter_Method;

class Markdown extends Twig_Extension
{
    /**
     * @var MarkdownInterface
     */
    protected $markdown;

    public function getName()
    {
        return 'Markdown';
    }

    public function getFilters()
    {
        return array(
            'markdown' => new Twig_Filter_Method($this, 'transform', array('is_safe' => array('html'))),
        );
    }

    /**
     * Transfer text to html
     *
     * @param string $text
     * @return string
     */
    public function transform($text)
    {
        return $this->getMarkdown()->transform($text);
    }

    /**
     * Set the Markdown parser to use
     *
     * @param MarkdownInterface $markdown
     */
    public function setMarkdown(MarkdownInterface $markdown)
    {
        $this->markdown = $markdown;
    }

    /**
     * @return MarkdownInterface
     */
    public function getMarkdown()
    {
        if (null === $this->markdown) {
            $this->markdown = new EdgeMarkdown();
        }

        return $this->markdown;
    }
}
