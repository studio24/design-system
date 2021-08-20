<?php
declare(strict_types=1);

namespace Studio24\DesignSystem\Parser;

use League\Flysystem\Filesystem;
use Studio24\DesignSystem\Config;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\HtmlParserException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

/**
 * Base functionality for HTML tag parsing in documentation layouts
 */
abstract class ParserAbstract
{
    protected Config $config;
    protected Filesystem $filesystem;
    protected Environment $twig;
    protected SymfonyStyle $output;
    protected string $currentFile;
    protected string $currentHtmlMatch;
    protected TagParser $htmlParser;

    public function __construct()
    {
        $this->htmlParser = new TagParser();
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param Environment $twig
     */
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    /**
     * @param SymfonyStyle $output
     */
    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * @param string $currentFile
     */
    public function setCurrentFile(string $currentFile): void
    {
        $this->currentFile = $currentFile;
    }

    /**
     * Return HTML tag to match for this parser
     * @return string
     */
    abstract public function getHtmlTag(): string;

    /**
     * Render HTML tag and return parsed HTML
     * @param array $params
     * @return string
     */
    abstract public function render(array $params): string;

    /**
     * Parse HTML and replace matching tags with rendered content
     * @param string $html
     * @return string
     * @throws HtmlParserException
     */
    public function parse(string $html): string
    {
        foreach ($this->htmlParser->matchAll($html, $this->getHtmlTag()) as $match) {
            $attributes = $this->htmlParser->extractAttributes($match);
            $this->currentHtmlMatch = $match;

            $rendered = $this->render($attributes);
            $html = $this->htmlParser->replace($match, $rendered, $html);
        }
        return $html;
    }

}