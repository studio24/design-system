<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Parser;

use League\Flysystem\Filesystem;
use Studio24\DesignSystem\Build;
use Studio24\DesignSystem\Config;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\DataArrayMissingException;
use Studio24\DesignSystem\Exception\InvalidFileException;
use Studio24\DesignSystem\Exception\MissingAttributeException;
use Studio24\DesignSystem\Exception\PathDoesNotExistException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

/**
 * Render <example> tags in documentation layouts
 */
class ExampleParser extends ParserAbstract
{
    public function __construct(Environment $twig, Config $config, SymfonyStyle $output, Filesystem $filesystem)
    {
        $this->setTwig($twig);
        $this->setConfig($config);
        $this->setOutput($output);
        $this->setFilesystem($filesystem);

        parent::__construct();
    }

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
        // Get params
        if (!isset($params['title'])) {
            throw new MissingAttributeException('You must set the title src, e.g. <example title="My component" src="filename.html">');
        }
        if (!isset($params['src'])) {
            throw new MissingAttributeException('You must set the attribute src, e.g. <example title="My component" src="filename.html">');
        }
        $title = $params['title'];
        $filename = $params['src'];
        $standalone = isset($params['standalone']) ? true : false;

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

        // Render example template as HTML
        $rendered = $this->twig->render($filename, $data);
        $html = $rendered;

        // If not standalone, embed code example in template
        if (!$standalone) {
            $data = [
                'title' => $title,
                'html' => $rendered
            ];
            $htmlPage = $this->twig->render('@DesignSystem/example-code.html.twig', $data);
        } else {
            $htmlPage = $html;
        }

        // Save example template
        $filename = $this->config->getHtmlFilename($filename);
        $destination = $this->config->buildPath(Config::CODE_PATH, $filename);
        $url = $this->config->getDistUrl($destination);
        try {
            $this->filesystem->write($destination, $htmlPage);

            if ($this->output->isVerbose()) {
                $this->output->text('* ' . $destination);
            }

        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot save example template to %s, error: %s', $filename, $exception->getMessage()));
        }

        // Return example HTML to docs page
        $data = [
            'title'  => $title,
            'url'    => $url,
            'html'   => $html,
        ];
        return $this->twig->render('@DesignSystem/partials/_example.html.twig', $data);
    }

}