<?php
declare(strict_types=1);

namespace Studio24\DesignSystem;

use Alchemy\Zippy\Zippy;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Studio24\DesignSystem\Exception\BuildException;
use Studio24\DesignSystem\Exception\ConfigException;
use Studio24\DesignSystem\Exception\AssetsException;
use Studio24\DesignSystem\Parser\ColorsParser;
use Studio24\DesignSystem\Parser\ExampleParser;
use Studio24\DesignSystem\Parser\Markdown;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Build
{
    private Config $config;
    private SymfonyStyle $output;
    private Filesystem $filesystem;
    private Markdown $markdown;
    private ?Environment $twig = null;
    private ExampleParser $example;

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

        // Set default file permissions
        $visibility = PortableVisibilityConverter::fromArray([
            'file' => [
                'public' => 0644,
                'private' => 0600,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ],
        ],
        Visibility::PUBLIC);
        $adapter = new LocalFilesystemAdapter($config->getRootPath(), $visibility);
        $this->filesystem = new Filesystem($adapter);
        $this->markdown = new Markdown();

        // Setup tags to output code examples from docs
        $this->example = new ExampleParser($this->getTwig(), $this->config, $output, $this->filesystem);
        $this->colors = new ColorsParser($this->getTwig(), $this->config, $this->filesystem);
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

        // Default template path for Twig templates
        $loader = new FilesystemLoader([
            $this->config->getFullPath('templates_path'),
        ]);

        // Use local project template path for Design System templates, if exists
        $paths = [];
        $templatePath = $this->config->buildPath($this->config->getFullPath('templates_path'), '/design-system/');
        if (is_dir($templatePath)) {
            $paths[] = $templatePath;
        }

        // Default template path for Design System temlates
        $paths[] = __DIR__ . '/../templates';
        $loader->setPaths($paths, 'DesignSystem');

        $options = ['debug' => true];
        $this->twig = new Environment($loader, $options);
        return $this->twig;
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
            $this->filesystem->createDirectory($destination);

        } catch (FilesystemException | UnableToDeleteDirectory $exception) {
            throw new BuildException(sprintf('Cannot clean destination folder, error: %s', $exception->getMessage()));
        }
    }

    /**
     * Build frontend assets via shell command
     *
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
     *
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
                    $destination = $this->config->buildPath(Config::ASSETS_PATH, $path);
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
    public function buildDocs()
    {
        $docsPath = $this->config->get('docs_path');
        $this->output->text(sprintf('Parsing documentation at %s', $docsPath));

        // Build list of doc layouts
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

                    // Build hierarchy of layouts
                    $subDirectory = trim($dir, '/');
                    if (!isset($pages[$subDirectory])) {
                        $pages[$subDirectory] = [];
                    }

                    $pages[$subDirectory][] = [
                        'location'      => $subDirectory,
                        'filename'      => $filename,
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

        // Sort layouts in each sub-directory
        foreach ($pages as $subDirectory => $children) {
            uasort($pages[$subDirectory], function($a, $b) {
                // Stick index layouts to top
                if ($a['filename'] === 'index') {
                    return -1;
                } elseif ($b['filename'] === 'index') {
                    return 1;
                }
                // Or sort using natural sort order
                return strnatcmp($a['title'], $b['title']);
            });
        }

        // Work out if any sub-directories do not include index layouts
        $noIndexPages = array_keys($pages);
        foreach ($pages as $subDirectory => $children) {
            foreach ($children as $item) {
                if ($item['filename'] === 'index') {
                    $key = array_search($subDirectory, $noIndexPages);
                    if ($key !== false) {
                        unset($noIndexPages[$key]);
                    }
                }
            }
        }

        // Build sibling nav
        $navigation = $this->config->get('navigation');
        $siblingNavigation = [];
        foreach ($pages as $subDirectory => $children) {
            $siblingNavigation[$subDirectory] = [];
            foreach ($children as $page) {
                $siblingNavigation[$subDirectory][] = [
                    'title' => $page['title'],
                    'url'   => $page['link'],
                    'in_navigation' => $this->config->inNavigation($page['link']),
                ];
            }
        }

        // Build layouts
        foreach ($pages as $subDirectory => $children) {
            foreach ($children as $page) {
                $this->buildDocsPage($page['source'], $page['destination'], $siblingNavigation[$subDirectory]);
            }
        }

        // Do we need to build any index layouts?
        foreach ($noIndexPages as $subDirectory) {
            $this->buildIndexPage($subDirectory, $siblingNavigation[$subDirectory]);
        }
    }

    /**
     * Render index page and save
     * @param string $directory Directory to save index page to
     * @param array $siblingNavigation Links to sibling layouts
     */
    public function buildIndexPage(string $directory, array $siblingNavigation)
    {
        $twig = $this->getTwig();
        $destination = Config::DIST_PATH . DIRECTORY_SEPARATOR . $directory . '/index.html';

        $data = [
            'date'  => new \DateTime(),
            'title' => ucfirst($directory),
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
     * @param array $siblingNavigation Links to sibling layouts
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

        // Parse markdown
        $html =  $this->markdown->render($markdown);

        // Parse <example> tag
        $this->example->setCurrentFile($sourcePath);
        $html = $this->example->parse($html);

        // Parse <colors> tag
        $this->colors->setCurrentFile($sourcePath);
        $html = $this->colors->parse($html);

        // Build Twig data
        $data = [
            'date'               => new \DateTime(),
            'contents'           => $html,
            'sibling_navigation' => $siblingNavigation,
        ];
        $data['current_url'] = $this->config->getDistUrl($destination);
        $data['navigation'] = $this->config->getNavigation($data['current_url']);

        // Use one-col if one page, or two-col if more than one page (in sub-directory)
        if (count($siblingNavigation) > 1) {
            $template = '@DesignSystem/content-page.html.twig';
        } else {
            $template = '@DesignSystem/content-page-no-siblings.html.twig';
        }
        $html = $twig->render($template, $data);

        $this->filesystem->write($destination, $html);
        if ($this->output->isVerbose()) {
            $this->output->text('* ' . $destination);
        }
    }

    /**
     * Create ZIP file of website assets for developer use
     *
     * @see https://github.com/alchemy-fr/Zippy
     */
    public function buildZipFile()
    {
        if (!$this->config->has('zip_folder')) {
            $this->output->text('Skipping, no ZIP folder defined in config');
            return false;
        }

        // Path to folder to add to ZIP archive (relative to project root)
        $zipFolder = $this->config->get('zip_folder');
        if (empty($zipFolder)) {
            $this->output->text('Skipping, no ZIP folder defined in config');
            return false;
        }
        $source = $this->config->getFullPath($zipFolder);
        if (!is_dir($source)) {
            throw new BuildException(sprintf('Cannot build ZIP archive since folder %s does not exit, full source path: %s', $zipFolder, $source));
        }

        // Name of ZIP folder / archive file
        $zipName = null;
        if ($this->config->has('zip_name')) {
            $zipName = $this->config->get('zip_name');
        }
        if (empty($zipName)) {
            $zipName = pathinfo($zipFolder, PATHINFO_BASENAME);
        }
        $destination = $this->config->getFullPath($this->config->buildPath(Config::ASSETS_PATH, $zipName)) . '.zip';

        try {
            $zippy = Zippy::load();
            $archive = $zippy->create($destination, [
                $zipName => $source
            ], true);

            if ($this->output->isVerbose()) {
                $this->output->text('* ' . $destination);
            }

            return true;

        } catch (\Alchemy\Zippy\Exception\ExceptionInterface $exception) {
            throw new BuildException(sprintf('Cannot build ZIP archive for folder %s, destination %s, error: %s', $zipFolder, $destination, $exception->getMessage()));
        }
    }

}
