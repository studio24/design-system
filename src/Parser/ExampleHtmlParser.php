<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Parser;

use Studio24\DesignSystem\Exception\MissingAttributeException;

/**
 * Render <exampleHtml> tags in documentation pages
 */
class ExampleHtmlParser extends ParserAbstract
{
    private ExampleParser $exampleFunction;

    /**
     * @param ExampleParser $exampleFunction
     */
    public function setExampleFunction(ExampleParser $exampleFunction): void
    {
        $this->exampleFunction = $exampleFunction;
    }

    /**
     * Return HTML tag to match for this parser
     * @return string
     */
    public function getHtmlTag(): string
    {
        return '<exampleHtml>';
    }

    /**
     * Render HTML tag and return parsed HTML
     * @param array $params
     * @return string
     */
    public function render(array $params): string
    {
        if (!isset($params['src'])) {
            throw new MissingAttributeException('You must set the attribute src, e.g. <exampleHtml src="filename.html">');
        }

        // Render HTML and output to page
        return $this->twig->render('@DesignSystem/partials/_example-html.html.twig', ['html' => $this->exampleFunction->getHtml($params['src'])]);
    }

}