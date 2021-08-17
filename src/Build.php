<?php
declare(strict_types=1);

namespace Studio24\DesignSystem;

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use Masterminds\HTML5;
use Parsedown;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\ConfigException;
use Studio24\DesignSystem\Exception\AssetsException;
use Studio24\DesignSystem\Parser\ExampleHtmlParser;
use Studio24\DesignSystem\Parser\ExampleParser;
use Studio24\DesignSystem\Parser\HtmlParser;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Build
{
    const DIR_CODE_EXAMPLES = Config::DIST_PATH . '/code/examples';
    const DIR_CODE_TEMPLATES = Config::DIST_PATH . '/code/templates';

    private Config $config;
    private SymfonyStyle $output;
    private Filesystem $filesystem;
    private Parsedown $markdown;
    private ?Environment $twig = null;
    private ExampleParser $example;
    private ExampleHtmlParser $exampleHtml;

    /**
     * Constructor
     * @param Config $config
     * @param SymfonyStyle $output
     * @throws ConfigException
     * @throws Exception\PathDoesNotExistException
     */
    public function __construct(Config $config, SymfonyStyle $output)
    {
        $this->config = $config;
        $this->output = $output;

        $adapter = new LocalFilesystemAdapter($config->getRootPath());
        $this->filesystem = new Filesystem($adapter);
        $this->markdown = new Parsedown();

        // Setup markdown special functions to output code examples
        $this->example = new ExampleParser();
        $this->example->setTwig($this->getTwig());
        $this->example->setConfig($this->config);
        $this->example->setOutput($output);
        $this->example->setFilesystem($this->filesystem);

        $this->exampleHtml = new ExampleHtmlParser();
        $this->exampleHtml->setExampleFunction($this->example);
        $this->exampleHtml->setTwig($this->getTwig());
    }

    /**
     * Return Twig object
     *
     * Default path: templates folder in your project root
     * @DesignSystem: templates folder in Design System repo
     *
     * @return Environment
     * @throws ConfigException
     */
    public function getTwig(): Environment
    {
        if ($this->twig instanceof Environment) {
            return $this->twig;
        }

        $options = [];
        if ($this->config->has('cache_path')) {
            $options = ['cache' => $this->config->getFullPath('cache_path')];
        } else {
            $options = ['cache' => sys_get_temp_dir()];
        }
        if ($this->config->get('debug')) {
            $options['debug'] = true;
        }

        // Default template path for Twig templates
        $loader = new FilesystemLoader([
            $this->config->getFullPath('templates_path'),
        ]);

        // Use local project template path for Design System temlates, if exists
        $paths = [];
        $templatePath = $this->config->buildPath($this->config->getFullPath('templates_path'), '/design-system/');
        if (is_dir($templatePath)) {
            $paths[] = $templatePath;
        }

        // Default template path for Design System temlates
        $paths[] = __DIR__ . '/../templates';
        $loader->setPaths($paths, 'DesignSystem');

        $this->templatesTwig = new Environment($loader, $options);
        return $this->templatesTwig;
    }

    /**
     * Delete all files from destination folder, so we can create a clean new set of files
     *
     * @throws BuildException
     */
    public function cleanDestination(): void
    {
        $destination = Config::DIST_PATH;
        try {
            $this->filesystem->deleteDirectory($destination);
        } catch (FilesystemException | UnableToDeleteDirectory $exception) {
            throw new BuildException(sprintf('Cannot clean destination folder, error: %s', $exception->getMessage()));
        }
        try {
            $this->filesystem->createDirectory($destination);
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            throw new BuildException(sprintf('Cannot create destination folder, error: %s', $exception->getMessage()));
        }
    }

    /**
     * Build frontend assets
     * @param bool $passthru Whether to output result of command as it runs or not
     * @throws AssetsException
     */
    public function buildAssets(bool $passthru = false)
    {
        $command = $this->config->get('assets_build_command');
        if (empty($command)) {
            throw new AssetsException("Cannot build assets since \$config['assets_build_command'] not set");
        }

        // Change dir, then run command
        $command = sprintf('cd %s && %s',$this->config->getRootPath(), $command);
        $output = '';

        if ($passthru) {
            passthru($command,$status);
        } else {
            exec($command,$output,$status);
        }

        if ($status !== 0) {
            $message = 'Command: ' . $command;
            if (!$passthru) {
                $message .= PHP_EOL . 'Output: ' . implode("\n", $output);
            }
            throw new AssetsException('Asset build failed. ' . $message);
        }
    }

    /**
     * Copy design system website assets
     * Copes files from /assets to ./_dist/assets/design-system of project
     * @throws BuildException
     */
    public function copyDesignSystemAssets()
    {
        $source = __DIR__  . '/../assets/';
        $adapter = new LocalFilesystemAdapter($source);
        $assetsFilesystem = new Filesystem($adapter);

        try {
            $listing = $assetsFilesystem->listContents('design-system', Filesystem::LIST_DEEP);

            /** @var \League\Flysystem\StorageAttributes $item */
            foreach ($listing as $item) {
                $path = $item->path();
                if ($item instanceof FileAttributes) {
                    $destination = $this->config->buildPath(Config::DIST_PATH . '/assets', $path);
                    $this->filesystem->write($destination, $assetsFilesystem->read($path));

                    if ($this->output->isVerbose()) {
                        $this->output->text('* ' . $destination);
                    }
                }
            }
        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot copy design system assets from source %s to dist, error: %s', $source, $exception->getMessage()));
        }
    }

    /**
     * Build markdown files as HTML
     * @throws BuildException
     */
    public function buildPages()
    {
        $docsPath = $this->config->get('docs_path');
        $this->output->text(sprintf('Parsing folder for markdown pages: %s', $docsPath));

        // Build list of pages
        $pages = [];
        try {
            $listing = $this->filesystem->listContents($docsPath, Filesystem::LIST_DEEP);

            /** @var \League\Flysystem\StorageAttributes $item */
            foreach ($listing as $item) {
                if ($item instanceof FileAttributes) {
                    $path = $item->path();
                    $pathinfo = pathinfo($path);
                    $dirname = $pathinfo['dirname'];
                    $filename = $pathinfo['filename'];
                    $extension = $pathinfo['extension'];

                    // Only build .md files
                    if (strtolower($extension) !== 'md') {
                        continue;
                    }

                    // Destination
                    if (strtoupper($filename) === 'README') {
                        $filename = 'index';
                    }
                    $dir = preg_replace('!^docs!', '', $dirname);
                    $destination =  Config::DIST_PATH . $dir . DIRECTORY_SEPARATOR . $filename . '.html';

                    // Get page title from first markdown heading, or filename
                    $markdown = $this->filesystem->read($path);
                    if (preg_match('/[#]+\s?(.+)/', $markdown, $m)) {
                        $title = $m[1];
                    } else {
                        $title = $filename;
                    }

                    // Build hierarchy of pages
                    $location = trim($dir, '/');
                    if (!isset($pages[$location])) {
                        $pages[$location] = [];
                    }

                    $pages[$location][] = [
                        'location'      => $location,
                        'title'         => $title,
                        'source'        => $path,
                        'destination'   => $destination,
                        'link'          => preg_replace('/^_dist/', '', $destination)
                    ];
                }
            }
        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot build markdown page from source %s to _dist, error: %s', $docsPath, $exception->getMessage()));
        }

        // Sort child pages
        foreach ($pages as $location => &$children) {
            uasort($children, function($a, $b){
                return strnatcmp($a['title'], $b['title']);
            });
        }

        // Build sibling nav
        $siblingNavigation = [];
        foreach ($pages as $location => $children) {
            $siblingNavigation[$location] = [];
            foreach ($children as $page) {
                $siblingNavigation[$location][] = [
                    'title' => $page['title'],
                    'url'   => $page['link'],
                ];
            }
        }

        // Build pages
        foreach ($pages as $location => $children) {
            // Index page (don't generate for root)
            if ($location !== "") {
                $this->buildIndexPage($location, $siblingNavigation[$location]);
            }

            // Page
            foreach ($children as $page) {
                $this->buildDocsPage($page['source'], $page['destination'], $siblingNavigation[$location]);
            }
        }
    }

    /**
     * Render index page and save
     * @param string $directory Directory to save index page to
     * @param array $siblingNavigation Links to sibling pages
     */
    public function buildIndexPage(string $directory, array $siblingNavigation)
    {
        $twig = $this->getTwig();
        $destination = Config::DIST_PATH . DIRECTORY_SEPARATOR . $directory . '/index.html';

        $data = [
            'sibling_navigation' => $siblingNavigation,
            'navigation' => $this->config->getNavigation($this->config->getDistUrl($destination)),
        ];
        $html = $twig->render('@DesignSystem/content-index.html.twig', $data);

        $this->filesystem->write($destination, $html);
        if ($this->output->isVerbose()) {
            $this->output->text('* ' . $destination);
        }
    }

    /**
     * Render markdown page and save
     * @param string $sourcePath Source file to read markdown from
     * @param string $destination Destination to save HTML page to
     * @param array $siblingNavigation Links to sibling pages
     * @throws BuildException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function buildDocsPage(string $sourcePath, string $destination, array $siblingNavigation)
    {
        $twig = $this->getTwig();

        try {
            $markdown = $this->filesystem->read($sourcePath);
        } catch (FilesystemException | UnableToReadFile $exception) {
            throw new BuildException(sprintf('Cannot load markdown docs file at %s', $sourcePath));
        }

        $html =  $this->markdown->text($markdown);

        // Parse example fragment: <example title="Button" src="components/button.html.twig" data="buttonText: A button">
        $this->example->setCurrentFile($sourcePath);
        $html = $this->example->parse($html);

        // Parse example code fragment: <exampleHtml src="components/button.html.twig">
        $this->exampleHtml->setCurrentFile($sourcePath);
        $html = $this->exampleHtml->parse($html);

        // Build Twig data
        $data = [
            'contents'           => $html,
            'sibling_navigation' => $siblingNavigation,
        ];
        $data['current_url'] = $this->config->getDistUrl($destination);
        $data['navigation'] = $this->config->getNavigation($data['current_url']);

        if ($sourcePath === 'docs/README.md' || empty($siblingNavigation)) {
            $template = '@DesignSystem/content-main.html.twig';
        } else {
            $template = '@DesignSystem/content-page.html.twig';
        }
        $html = $twig->render($template, $data);

        $this->filesystem->write($destination, $html);
        if ($this->output->isVerbose()) {
            $this->output->text('* ' . $destination);
        }
    }

    /**
     * Build full page templates and link to design system site
     *
     * @throws BuildException
     * @throws ConfigException
     * @throws FilesystemException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function buildTemplates()
    {
        $templates = $this->config->get('build_templates');
        if (!is_array($templates)) {
            return;
        }

        // Render templates found in config variable 'build_templates'
        $savedTemplates = [];
        foreach ($templates as $name => $path) {
            $rootPath = $this->config->buildPath($this->config->get('templates_path'), $path);

            // Single file
            if ($this->filesystem->fileExists($rootPath)) {
                if ($this->isTwigFile($path)) {
                    $data = $this->loadTemplateData($path);
                    $destination = $this->renderTemplateToDest($path, Build::DIR_CODE_TEMPLATES, $data);
                    $savedTemplates[] = $destination;
                }
                continue;
            }

            // Directory
            try {
                $listing = $this->filesystem->listContents($rootPath, true);

                /** @var \League\Flysystem\StorageAttributes $item */
                foreach ($listing as $item) {
                    if ($item instanceof \League\Flysystem\FileAttributes && $this->isTwigFile($item->path())) {
                        $data = $this->loadTemplateData($item->path());
                        $twigPath = preg_replace('!^' . preg_quote($this->config->get('templates_path')) . '!', '', $item->path());
                        $destination = $this->renderTemplateToDest($twigPath, Build::DIR_CODE_TEMPLATES, $data);
                        $savedTemplates[] = $destination;
                    }
                }
            } catch (FilesystemException $exception) {
                throw new BuildException(sprintf('Cannot build templates from directory %s, error: %s', $path, $exception->getMessage()));
            }
        }

        // Build index page
        $templateLinks = [];
        $groupTemplatesLinks = [];
        foreach ($savedTemplates as $destination) {
            $destination = preg_replace('!^' . preg_quote(Build::DIR_CODE_TEMPLATES) . '!', '', $destination);
            $destination = ltrim($destination, '/');
            $directory = dirname($destination);
            $label = basename($destination);
            $url = $this->config->getDistUrl($destination);

            // Top-level templates
            if ($directory === '.') {
                $templateLinks[] = [
                    'title' => $label,
                    'url' => $url,
                ];
                continue;
            }

            // Templates grouped buy sub-directory
            if (!isset($groupTemplatesLinks[$directory])) {
                $groupTemplatesLinks[$directory] = [];
            }
            $groupTemplatesLinks[$directory][] = [
                'title' => $label,
                'url' => $url,
            ];
        }

        // Sort templates
        uasort($templateLinks, function($a, $b){
            return strnatcmp($a['title'], $b['title']);
        });
        foreach ($groupTemplatesLinks as $directory => &$children) {
            uasort($children, function($a, $b){
                return strnatcmp($a['title'], $b['title']);
            });
        }

        // Render template index page
        $destination = $this->config->buildPath(Build::DIR_CODE_TEMPLATES, 'index.html');
        $currentUrl = $this->config->getDistUrl($destination);
        $data = [
            'templates' => $templateLinks,
            'group_templates' => $groupTemplatesLinks,
            'current_url' => $currentUrl,
            'navigation' => $this->config->getNavigation($currentUrl),
        ];
        $html = $this->getTwig()->render('@DesignSystem/content-templates.html.twig', $data);
        try {
            $this->filesystem->write($destination, $html);
            if ($this->output->isVerbose()) {
                $this->output->text('* ' . $destination);
            }

        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot render template index to destination %s, error: %s', $destination, $exception->getMessage()));
        }
    }

    /**
     * Whether the file is a Twig template (has a .twig file extension)
     * @param string $path
     * @return bool
     */
    public function isTwigFile(string $path): bool
    {
        return (pathinfo($path, PATHINFO_EXTENSION) === 'twig');
    }

    /**
     * Load template data array to pass to Twig, if exists
     *
     * @param string $templatePath
     * @return array
     * @throws FilesystemException
     */
    public function loadTemplateData(string $templatePath): array
    {
        $dataPath = $templatePath . '.php';
        if ($this->filesystem->fileExists($dataPath)) {
            require $dataPath;
            if (isset($data) && is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    /**
     * Render a template and output to destination folder
     * @param string $templatePath Config to template file, relative to template root
     * @param string $destFolder Folder to save outputted file to, relative to project root
     * @return string Destination path template saved to
     * @throws BuildException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderTemplateToDest(string $templatePath, string $destFolder, array $data = []): string
    {
        $twig = $this->getTwig();

        // Calculate destination path
        $directory = dirname($templatePath);
        $filename = $this->config->getHtmlFilename($templatePath);
        if ($directory !== '.') {
            $destination = $this->config->buildPath($destFolder, $directory . DIRECTORY_SEPARATOR . $filename);
        } else {
            $destination = $this->config->buildPath($destFolder, $filename);
        }

        $html = $twig->render($templatePath, $data);
        try {
            $this->filesystem->write($destination, $html);
            if ($this->output->isVerbose()) {
                $this->output->text('* ' . $destination);
            }
            return $destination;

        } catch (FilesystemException | UnableToWriteFile $exception) {
            throw new BuildException(sprintf('Cannot render template file %s to destination %s, error: %s', $templatePath, $destination, $exception->getMessage()));
        }
    }

}
