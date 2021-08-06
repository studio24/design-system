<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Studio24\DesignSystem\Exception\HtmlParserException;
use Studio24\DesignSystem\Parser\HtmlParser;

final class ParseSpecialFunctionsTest extends TestCase
{

    public function testMatchAll()
    {
        $parser = new HtmlParser();

        $html = <<<EOD
<p>Some text</p>

<example title="Tables" src="components/tables.html.twig">

<p>Testing</p>

<example title="Tables 2" src="components/tables.html.twig", data="foo: bar, name: test">

<p>More text</p>

EOD;

        $matches = $parser->matchAll($html, '<example>');
        $this->assertSame('<example title="Tables" src="components/tables.html.twig">', $matches[0]);
        $params = $parser->extractAttributes($matches[0]);
        $this->assertSame('Tables', $params['title']);
        $this->assertSame('components/tables.html.twig', $params['src']);

        $this->assertSame('<example title="Tables 2" src="components/tables.html.twig", data="foo: bar, name: test">', $matches[1]);
        $params = $parser->extractAttributes($matches[1]);
        $this->assertSame('Tables 2', $params['title']);
        $this->assertSame('bar', $params['data']['foo']);
        $this->assertSame('test', $params['data']['name']);
    }

    public function testInvalidHtmlTag()
    {
        $parser = new HtmlParser();

        $this->expectException(HtmlParserException::class);
        $matches = $parser->matchAll('<p>some text</p>', 'example');
    }


}