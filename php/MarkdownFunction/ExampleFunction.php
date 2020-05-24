<?php
declare(strict_types=1);

namespace Studio24\Apollo\MarkdownFunction;

use Studio24\Apollo\Exception\BuildException;

class ExampleFunction extends MarkdownFunctionAbstract
{
    protected $twig;


    public function __construct()
    {
        // @todo pass what I need into this function, this is way cleaner!
    }

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
    public function getFunctionRegex(): array
    {
        return [
            '{{ example(STRING) }}',
            '{{ example(STRING, ARRAY) }}',
        ];
    }

    /**
     * Render special function as HTML
     * @param string $html
     * @param mixed ...$params
     * @return string
     */
    public function render(string $html, ...$params): string
    {
        $template = $params[0];
        $templateData = [];
        if (isset($params[1]) && is_array($params[1])) {
            $templateData = $params[1];
        }

        if (!empty($templateFolder)) {
            $template = rtrim($templateFolder, '/') . '/' . ltrim($template, '/');
        }

        $twig = $this->getTwig();
        $rendered = $twig->render($template, $templateData);

        $loader = $twig->getLoader();
        $template = $loader->getSourceContext($template);
        $shortName = str_replace('.html.twig', '', $template->getName());
        $name = preg_replace('/[-_]/', ' ', $shortName);


        $destination = $destPath . '/code.' . basename($template->getName(), '.twig');

        var_dump($templateFolder, $destination);exit;
        if (!file_put_contents($destination, $rendered)) {
            throw new BuildException(sprintf('Cannot save example code Twig template %s to %s', $name, $destination));
        }

        return $twig->render('templates/example.html.twig', [
            'name' => $name,
            'short_name' => $shortName,
            'html_code_url' => 'code.',
            'html' => $rendered,
            'twig_template_name' => $template->getName(),
            'twig' => $template->getCode()
        ]);
    }

}