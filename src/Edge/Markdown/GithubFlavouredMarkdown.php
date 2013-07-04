<?php

namespace Edge\Markdown;

use Michelf\MarkdownExtra;

class GithubFlavouredMarkdown implements MarkdownInterface
{
    /**
     * Apply Github flavoured markdown to plain text, then passes through MarkdownExtra
     *
     * @param string $text
     * @return string
     */
    public function transform($text)
    {
        $markdown = new MarkdownExtra();
        $text     = $this->applyFlavour($text);

        return $markdown->transform($text);
    }

    /**
     * Taken from https://gist.github.com/koenpunt/3194002
     *
     * @param string $text
     * @return string
     */
    public function applyFlavour($text)
    {
        # Extract pre blocks
        $extractions = array();

        $text = preg_replace_callback('/<pre>.*?<\/pre>/s', function($matches) use (&$extractions){
            $match = $matches[0];
            $md5 = md5($match);
            $extractions[$md5] = $match;
            return "{gfm-extraction-${md5}}";
        }, $text);

        # prevent foo_bar_baz from ending up with an italic word in the middle
        $text = preg_replace_callback('/(^(?! {4}|\t)\w+_\w+_\w[\w_]*)/s', function($matches){
            $x = $matches[0];
            $x_parts = str_split($x);
            sort($x_parts);
            if( substr(implode('', $x_parts), 0, 2) == '__' ){
                return str_replace('_', '\_', $x);
            }
        }, $text);

        # in very clear cases, let newlines become <br /> tags
        $text = preg_replace_callback('/^[\w\<][^\n]*\n+/m', function($matches){
            $x = $matches[0];
            if( !preg_match('/\n{2}/', $x) ){
                $x = trim($x);
                $x .= "  \n";
            }
            return $x;
        }, $text);

        # Insert pre block extractions
        $text = preg_replace_callback('/\{gfm-extraction-([0-9a-f]{32})\}/', function($matches) use (&$extractions){
            $match = $matches[1];
            return "\n\n" . $extractions[$match];
        }, $text);

        return $text;
    }
}