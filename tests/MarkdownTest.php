<?php

use PHPUnit\Framework\TestCase;
use Studio24\Apollo\Markdown;

final class MarkdownTest extends TestCase
{
    protected $markdown1 = <<<EOD
# Title

My text here
EOD;

    protected $markdown2 = <<<EOD
---
title: Testing
---
# Title

My text here
EOD;

    public function testSimpleMarkdown()
    {
        $markdown = new Markdown();
        $html = $markdown->parse($this->markdown1);

        $this->assertStringContainsString('<h1>Title</h1>', $html);

        $htmlCopy = $markdown->getHtml();
        $this->assertStringNotContainsString('---', $htmlCopy);
    }

    public function testFrontMatter()
    {
        $markdown = new Markdown();
        $html = $markdown->parse($this->markdown1);
        $this->assertNull($markdown->getFrontMatter('title'));

        $html = $markdown->parse($this->markdown2);

        $this->assertEquals('Testing', $markdown->getFrontMatter('title'));
        $this->assertNull($markdown->getFrontMatter('fake'));
    }

    public function testMarkdownFromFile()
    {
        $markdown = new Markdown();
        $html = $markdown->parseFile(__DIR__ . '/test-markdown.md');

        $this->assertEquals('As You Like It', $markdown->getFrontMatter('title'));
        $this->assertStringContainsString("All the world's a stage", $html);
    }

    public function testExpandArray()
    {
        $markdown = new Markdown();

        $data = $markdown->filterParamValue("[key: value, key2: value2]");
        $this->assertIsArray($data);
        $this->assertEquals('value', $data['key']);
        $this->assertEquals('value2', $data['key2']);

        $data = $markdown->filterParamValue("Some text here");
        $this->assertIsString($data);
        $this->assertEquals("Some text here", $data);
    }

    protected $markdown3 = <<<EOD
## Sub-title

My text goes here.

{{ example('test.html.twig') }}
EOD;

    protected $markdown4 = <<<EOD
## Sub-title

My text goes here.

{{ example('test.html.twig') }}

And here you go.

{{ example('test.html.twig', [name: Fred, height: 200]) }}
EOD;

    public function testMarkdownToHtml()
    {
        $markdown = new Markdown();

        $loader = new \Twig\Loader\FilesystemLoader([__DIR__, realpath(__DIR__ . '/../design-system/')]);
        $twig = new \Twig\Environment($loader);
        $markdown->setTwig($twig);

        $html = $markdown->parse($this->markdown3);
        $this->assertStringContainsString("My text goes here", $html);

        $html = $markdown->parseSpecialFunctions($html, '', __DIR__, sys_get_temp_dir());
        $this->assertStringNotContainsString("{{ example(", $html);
        $this->assertStringContainsString("HTML", $html);
        $this->assertStringContainsString("Name: ", $html);
        $this->assertStringNotContainsString("Fred", $html);

        $html = $markdown->parse($this->markdown4);
        $this->assertStringContainsString("And here you go", $html);

        $html = $markdown->parseSpecialFunctions($html, '', __DIR__, sys_get_temp_dir());
        $this->assertStringNotContainsString("{{ example(", $html);
        $this->assertStringContainsString("Name: Fred", $html);
        $this->assertStringContainsString("Height: 200", $html);
    }

}