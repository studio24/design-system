<?php
declare(strict_types=1);

namespace Studio24\Apollo;

use Spatie\YamlFrontMatter\YamlFrontMatter;
use Spatie\YamlFrontMatter\Document;
use Studio24\Apollo\Exception\BuildException;
use Parsedown;
use Studio24\Apollo\Traits\OutputTrait;
use Twig\Environment;

class Markdown
{
    use OutputTrait;

    /**
     * Array of front matter variables that can be setup in Markdown files
     * @var string[]
     */
    protected $validFrontMatter = [
        'title'
    ];

    /** @var string */
    protected $html = '';

    /** @var Document */
    protected $frontMatter;

    /**
     * Markdown parser
     * @var \Parsedown
     */
    protected $markdown;

    /** @var Environment */
    protected $twig;

    public function __construct()
    {
        $this->markdown = new Parsedown();
    }

    /**
     * Set Twig template object
     * @param Environment $twig
     */
    public function setTwig(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getTwig(): Environment
    {
        if (!($this->twig instanceof Environment)) {
            throw new BuildException('You must set Twig object via Markdown::setTwig()');
        }
        return $this->twig;
    }

    /**
     * Return HTML
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Return front matter
     * @param string $name Name of the front matter variable to return
     * @return string|null Front matter value, or null on failure
     */
    public function getFrontMatter($name): ?string
    {
        return $this->frontMatter->matter($name);
    }

    /**
     * Return array of front matter
     * @return array
     */
    public function getAllFrontMatter(): array
    {
        return $this->frontMatter->matter();
    }

    /**
     * Parse markdown and return HTML
     * @param string $markdown Markdown string
     * @return string
     */
    public function parse(string $markdown): string
    {
        $this->frontMatter = YamlFrontMatter::parse($markdown);
        $this->html = $this->markdownToHtml($this->frontMatter->body());
        return $this->html;
    }

    /**
     * Parse markdown and return HTML
     * @param string $sourcePath Path to markdown file
     * @return string
     */
    public function parseFile(string $sourcePath): string
    {
        return $this->parse(file_get_contents($sourcePath));
    }

    /**
     * Parse markdown string to HTML, expanding any special functions found in code
     * @param string $markdown
     * @return string
     */
    public function markdownToHtml(string $markdown): string
    {
        $html = $this->markdown->text($markdown);

        return $html;
    }

}