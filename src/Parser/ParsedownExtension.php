<?php

namespace Studio24\DesignSystem\Parser;

use Parsedown;

/**
 * Extension to ParsedownExtension to convert .md links to .html
 */
class ParsedownExtension extends Parsedown
{

    /**
     * Override inlineLink functionality
     * @param $Excerpt
     * @return array|void
     */
    protected function inlineLink($Excerpt)
    {
        $markdown = parent::inlineLink($Excerpt);
        $this->htmlifyLink($markdown['element']['attributes']['href']);
        return $markdown;
    }

    /**
     * Convert .md links into .html links
     * @param string $link
     */
    public function htmlifyLink(string &$link)
    {
        $link = preg_replace('/\.md$/i', '.html', $link);
    }

}