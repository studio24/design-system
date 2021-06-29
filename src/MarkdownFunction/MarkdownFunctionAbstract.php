<?php
declare(strict_types=1);

namespace Studio24\Apollo\MarkdownFunction;

use Studio24\Apollo\Exception\BuildException;

abstract class MarkdownFunctionAbstract
{

    /**
     * Return array of regex to match the function
     *
     * Should be in format {{ function_name(STRING) }}
     * Can pass params via: STRING or ARRAY
     * STRING matches a string in single quotes: 'string'
     * ARRAY matches an array in Twig format: [key: value, key2: value2]
     *
     * @return array
     */
    abstract public function getFunctionRegex(): array;

    /**
     * Render special function as HTML
     * @param string $html
     * @param mixed ...$params
     * @return string
     */
    abstract public function render(string $html, ...$params): string;

    /**
     * Parse special functions in markdown
     *
     * @param string $html Markdown-parsed HTML
     * @return string $html
     * @throws BuildException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function parseSpecialFunctions(string $html): string
    {
        foreach ($this->getFunctionRegex() as $regex) {
            $regex = '/' . preg_quote($regex) . '/U';
            $regex = preg_replace('/STRING/', "'(.+)'", $regex);
            $regex = preg_replace('/ARRAY/', "(\[.+\])", $regex);
            $results = [];

            // Build array of matches and function params
            if (preg_match_all($regex, $html, $matches)) {
                foreach ($matches as $key => $array) {
                    if ($key === 0) {
                        foreach ($array as $matchNum => $value) {
                            $results[$matchNum] = [
                                'match' => $value,
                                'params' => [],
                            ];
                        }
                        continue;
                    }
                    foreach ($array as $matchNum => $value) {
                        $results[$matchNum]['params'][] = $this->filterParamValue($value);
                    }
                }
            }

            // Parse functions
            foreach ($results as $item) {

                if (isset($item['params'][1])) {
                    $rendered = $this->render($html, $item['params'][0]);
                } else {
                    $rendered = $this->render($html, $item['params'][0], $item['params'][1]);
                }

                $html = preg_replace('/' . preg_quote($item['match'], '/') . '/', $rendered, $html);
            }
        }
        return $html;
    }

    /**
     * Expand Twig style array into a PHP array
     * @param string $content Twig style array (e.g. [key: value, key: value2])
     * @return string|array
     */
    public function filterParamValue(string $content)
    {
        if (!preg_match('/\[.+\]/', $content)) {
            return $content;
        }

        $data = [];
        $content = trim($content, '[]');
        $content = explode(', ', $content);
        foreach ($content as $item) {
            $pairs = explode(':', $item);
            $data[$pairs[0]] = trim($pairs[1]);
        }
        return $data;
    }

}