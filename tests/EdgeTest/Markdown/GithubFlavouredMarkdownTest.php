<?php

namespace Edge\Markdown;

use PHPUnit_Framework_TestCase;

class GithubFlavouredMarkdownTest extends PHPUnit_Framework_TestCase
{
    public function testShouldNotTouchSingleUnderscoresInsideWords()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("foo_bar", $gfm->applyFlavour("foo_bar"));
    }

    public function testShouldNotTouchUnderscoresInCodeBlocks()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("    foo_bar_baz", $gfm->applyFlavour("    foo_bar_baz"));
    }

    public function testShouldNotTouchUnderscoresInPreBlocks()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("\n\n<pre>\nfoo_bar_baz\n</pre>", $gfm->applyFlavour("<pre>\nfoo_bar_baz\n</pre>"));
    }

    public function testShouldNotTreatPreBlocksWithPreTextDifferently()
    {
        $gfm = new GithubFlavouredMarkdown();
        $a = "\n\n<pre>\nthis is `a\\_test` and this\\_too\n</pre>";
        $b = "hmm<pre>\nthis is `a\\_test` and this\\_too\n</pre>";
        $this->assertEquals(substr($gfm->applyFlavour($a), 2), substr($gfm->applyFlavour($b), 3));
    }

    public function testShouldEscapeTwoOrMoreUnderscoresInsideWords()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("foo\\_bar\\_baz", $gfm->applyFlavour("foo_bar_baz"));
    }

    public function testShouldTurnNewlinesIntoBrTagsInSimpleCases()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("foo  \nbar", $gfm->applyFlavour("foo\nbar"));
    }

    public function testShouldConvertNewlinesInAllGroups()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("apple  \npear  \norange\n\nruby  \npython  \nerlang", $gfm->applyFlavour("apple\npear\norange\n\nruby\npython\nerlang"));
    }

    public function testShouldConvertNewlinesInEvenLongGroups()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("apple  \npear  \norange  \nbanana\n\nruby  \npython  \nerlang", $gfm->applyFlavour("apple\npear\norange\nbanana\n\nruby\npython\nerlang"));
    }

    public function testShouldNotConvertNewlinesInLists()
    {
        $gfm = new GithubFlavouredMarkdown();
        $this->assertEquals("# foo\n# bar", $gfm->applyFlavour("# foo\n# bar"));
        $this->assertEquals("* foo\n* bar", $gfm->applyFlavour("* foo\n* bar"));
    }
}