<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Studio24\DesignSystem\Parser\Markdown;

class MarkdownTest extends TestCase
{

    public function testMarkdown()
    {
        $markdown = new Markdown();

        $text = <<<EOD
# Hello testing 

Link https://www.bbc.co.uk/testing.md

[Internal link](docs/testing.md) 

## Sub-heading

More text here

* One
* Two
* Three

EOD;

        $html = $markdown->render($text);

        $this->assertStringContainsString('<a href="https://www.bbc.co.uk/testing.md">https://www.bbc.co.uk/testing.md</a>', $html, 'Auto-link, do not convert external .md links');
        $this->assertStringContainsString('<a href="docs/testing.html">Internal link</a>', $html, 'Convert local .md links');
        $this->assertStringNotContainsString('<h1><a id="hello-testing" href="#hello-testing" class="heading-permalink" aria-hidden="true" title="Permalink">¶</a>Hello testing</h1>', $html, 'Auto-link headings');
        $this->assertStringContainsString('<h2><a id="sub-heading" href="#sub-heading" class="heading-permalink" aria-hidden="true" title="Permalink">¶</a>Sub-heading</h2>', $html, 'Auto-link headings');
    }

}
