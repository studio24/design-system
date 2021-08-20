<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Parser;

use League\Flysystem\Filesystem;
use Studio24\DesignSystem\Build;
use Studio24\DesignSystem\Config;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\ColorsTagException;
use Studio24\DesignSystem\Exception\DataArrayMissingException;
use Studio24\DesignSystem\Exception\InvalidFileException;
use Studio24\DesignSystem\Exception\MissingAttributeException;
use Studio24\DesignSystem\Exception\PathDoesNotExistException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

/**
 * Render <example> tags in documentation layouts
 */
class ColorsParser extends ParserAbstract
{
    public function __construct(Environment $twig, Config $config, Filesystem $filesystem)
    {
        $this->setTwig($twig);
        $this->setConfig($config);
        $this->setFilesystem($filesystem);

        parent::__construct();
    }

    /**
     * Return HTML tag to match for this parser
     * @return string
     */
    public function getHtmlTag(): string
    {
        return '<colors>';
    }

    /**
     * Render HTML tag and return parsed HTML
     * @param array $params
     * @return string
     */
    public function render(array $params): string
    {
        // Get params
        if (!isset($params['src'])) {
            throw new MissingAttributeException(sprintf('Missing attribute src. Error with tag %s in doc file %s', $this->currentHtmlMatch, $this->currentFile));
        }

        // Load data
        try {
            $path = $this->config->get('docs_path') . '/' . $params['src'];
            $json = $this->filesystem->read($path);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (FilesystemException | UnableToReadFile $exception) {
            throw new ColorsTagException(sprintf('Cannot read data file at %s. Error with tag %s in doc file %s', $path, $this->currentHtmlMatch, $this->currentFile));
        } catch (\JsonException $exception) {
            throw new ColorsTagException(sprintf('Invalid JSON data (error %s). Error with tag %s in doc file %s', $exception->getMessage(), $this->currentHtmlMatch, $this->currentFile));
        }

        // Validate data
        $colours = [];
        foreach ($data as $item) {
            $this->keyExists($item, 'title');
            $this->keyExists($item, 'colors');
            $this->isArray($item, 'colors');
            foreach ($item['colors'] as $color) {
                $this->keyExists($color, 'variable');
                $this->keyExists($color, 'color');
            }
        }

        // Render colours template and return to docs page
        $data = ['colors' => $data];
        if (isset($params['caption'])) {
            $data['caption'] = $params['caption'];
        }
        return $this->twig->render('@DesignSystem/partials/_colors.html.twig', $data);
    }

    /**
     * Check a key exists
     * @param array $data
     * @param string $key
     * @throws ColorsTagException
     */
    public function keyExists(array $data, string $key)
    {
        if (!isset($data[$key])) {
            throw new ColorsTagException(sprintf('Colour data array invalid, missing key %s. Error with tag %s in doc file %', $key, $this->currentHtmlMatch, $this->currentFile));
        }
    }

    /**
     * Check key is an array
     * @param array $data
     * @param ?string $key
     * @throws ColorsTagException
     */
    public function isArray(array $data, string $key = null)
    {
        if (!is_array($data[$key])) {
            throw new ColorsTagException(sprintf('Color data array invalid, key %s is not an array. Error with tag %s in doc file %', $key, $this->currentHtmlMatch, $this->currentFile));
        }
    }

}