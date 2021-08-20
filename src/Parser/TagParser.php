<?php

namespace Studio24\DesignSystem\Parser;

use Masterminds\HTML5;
use Studio24\DesignSystem\Exception\HtmlParserException;

/**
 * Helper functions to parse tags and return HTML
 */
class TagParser
{

    /**
     * Match all HTML tags in an HTML string
     *
     * @param string $html HTML string to match tags in
     * @param string $htmlTag HTML tag to extract, e.g. <example>
     * @return array
     */
    public function matchAll(string $html, string $htmlTag): array
    {
        if (!preg_match('/^<.+>$/', $htmlTag)) {
            throw new HtmlParserException(sprintf('HTML tag must start and end with < >, passed tag: %', $htmlTag));
        }

        // Build array of matches and function params
        $regex = '/' . rtrim($htmlTag, '>') . ' .+>/U';
        if (preg_match_all($regex, $html, $matches)) {
            return $matches[0];
        }
        return [];
    }

    /**
     * Replace new HTML in original HTML string
     *
     * @param string $match HTML to match
     * @param string $replace HTML to replace
     * @param string $html Original HTML string to run the replace operation in
     * @return array|string|string[]|null
     */
    public function replace(string $match, string $replace, string $html)
    {
        return preg_replace('/' . preg_quote($match, '/') . '/', $replace, $html);
    }

    /**
     * Return key=>value pairs of attributes from the first HTML node in an HTML string
     *
     * @param string $html
     * @return array
     */
    public function extractAttributes(string $html): array
    {
        $html5 = new HTML5();
        $fragment = $html5->loadHTMLFragment($html);
        $attributes = [];

        /** @var \DOMAttr $attribute */
        foreach ($fragment->firstChild->attributes as $attribute) {
            if ($attribute->name === 'data') {
                $attributes[$attribute->name] = $this->filterDataAttribute($attribute->value);
                continue;
            }
            $attributes[$attribute->name] = $attribute->value;
        }

        return $attributes;
    }

    /**
     * Filter data attribute and return array of key->value pairs
     *
     * @param string $content Twig style data array (e.g. key: value, key: value2
     * @return array
     */
    public function filterDataAttribute(string $content): array
    {
        $data = [];

        $params = explode(',', $content);
        foreach ($params as $param) {
            $pairs = explode(':', $param);

            // Cleanup
            $name = trim($pairs[0], " '\"");
            $value = trim($pairs[1], " '\"");

            $data[$name] = $value;
        }

        return $data;
    }

}