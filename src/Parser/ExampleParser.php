<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Parser;

use Studio24\DesignSystem\Config;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\DataArrayMissingException;
use Studio24\DesignSystem\Exception\InvalidFileException;
use Studio24\DesignSystem\Exception\MissingAttributeException;
use Studio24\DesignSystem\Exception\PathDoesNotExistException;

/**
 * Render <example> tags in documentation pages
 */
class ExampleParser extends ParserAbstract
{
    /**
     * Return HTML tag to match for this parser
     * @return string
     */
    public function getHtmlTag(): string
    {
        return '<example>';
    }

    /**
     * Render HTML tag and return parsed HTML
     * @param array $params
     * @return string
     */
    public function render(array $params): string
    {
        if (!isset($params['title'])) {
            throw new MissingAttributeException('You must set the title src, e.g. <example title="My component" src="filename.html">');
        }
        if (!isset($params['src'])) {
            throw new MissingAttributeException('You must set the attribute src, e.g. <example title="My component" src="filename.html">');
        }
        $title = $params['title'];
        $filename = $params['src'];

        // Load data, if passed in HTML tag
        if (isset($params['data']) && is_array($params['data'])) {
            $data = $params['data'];
        }
        if (isset($params['data-src'])) {
            $path = $this->config->getFullPath('templates_path') . DIRECTORY_SEPARATOR . $params['data-src'];
            if (!file_exists($path)) {
                throw new PathDoesNotExistException(sprintf('Cannot load data file from %s', $params['data-src']));
            }
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'json':
                    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                    break;
                case 'php':
                    require $path;
                    if (!isset($data) || !is_array($data)) {
                        throw new DataArrayMissingException(sprintf('Cannot load $data array from PHP file %s', $params['data-src']));
                    }
                    break;
                default:
                    throw new InvalidFileException(sprintf('Data file extension %s not recognised for file %s', $extension, $params['data-src']));
            }
        }
        if (!isset($data)) {
            $data = [];
        }

        // Render code template
        $rendered = $this->twig->render($filename, $data);
        $this->html[$filename] = $rendered;

        // Generate standalone code example
        $template = 'example-code.html.twig';
        $data = ['title' => $title, 'html' => $rendered];
        $htmlPage = $this->twig->render('@DesignSystem/example-code.html.twig', $data);

        // Save code template
        $filename = basename($filename, '.twig');
        $filename = basename($filename, '.html');
        $destination = $this->config->buildPath(Config::DIST_PATH, 'code/' . $filename . '.html');
        $url = $this->config->getDistUrl($destination);
        try {
            $this->filesystem->write($destination, $htmlPage);

            if ($this->output->isVerbose()) {
                $this->output->text('* ' . $destination);
            }

        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot save code template %s, error: %s', $filename, $exception->getMessage()));
        }

        // Render example template & return it
        $data = [
            'title'  => $title,
            'url'    => $url,
        ];
        return $this->twig->render('@DesignSystem/partials/_example.html.twig', $data);
    }

    /**
     * Return rendered HTML for a previous example function call
     *
     * Used to help output HTML to markdown documentation pages
     *
     * @param string $filename
     * @return string|null
     */
    public function getHtml(string $filename): ?string
    {
        if (isset($this->html[$filename])) {
            return $this->html[$filename];
        }
        return null;
    }

}