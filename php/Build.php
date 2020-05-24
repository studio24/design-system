<?php
declare(strict_types=1);

namespace Studio24\Apollo;

use Studio24\Apollo\Exception\BuildException;
use Studio24\Apollo\Exception\ConfigException;
use Studio24\Apollo\Exception\AssetsException;
use Studio24\Apollo\Traits\OutputTrait;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Build
{
    use OutputTrait;

    /**
     * Default config values, can be overridden in config.php
     * @var array
     */
    protected $config = [
        'debug'             => false,
        'source_path'       => null,
        'destination_path'  => null,
        'cache_path'        => null,
        'build_command'     => 'npm run build',
        'navigation'        => [
            'Get started'   => 'get-started.md',
            'Guidelines'    => 'guidelines/',
            'Components'    => 'components/',
            'Examples'      => 'examples/',
            'Support'       => 'support.md'
        ],
    ];

    /**
     * Path to root project folder
     * @var string
     */
    protected $rootPath;

    /** @var string */
    protected $sourcePath;

    /** @var string */
    protected $destPath;

    /** @var Environment */
    protected $twig;

    /** @var Markdown */
    protected $markdown;

    /**
     * Initialise Apollo Build
     * @param string $configPath Path to config file
     * @param bool $autoReload Whether to auto reload Twig templates on file change
     * @throws ConfigException
     */
    public function __construct(string $configPath, $autoReload = true)
    {
        $this->rootPath = realpath(__DIR__ . '/../');
        if ($this->rootPath === false) {
            throw new ConfigException('Cannot calculate root project path at ./../');
        }

        // Set local config
        if (!file_exists($configPath)) {
            throw new ConfigException(sprintf('Config file does not exist at %s', $configPath));
        }
        require $configPath;
        if (!isset($config) || !is_array($config)) {
            throw new ConfigException('Config file must contain the $config variable and it must be an array');
        }
        foreach ($this->config as $name => $value) {
            if (isset($config[$name])) {
                $this->config[$name] = $config[$name];
            }
        }
        $this->setSourcePath($this->getPath($this->config('source_path')));
        $this->setDestPath($this->getPath($this->config('destination_path')));

        // Twig
        $cachePath = $this->getPath($this->config('cache_path'));
        if (!is_writable($cachePath)) {
            throw new ConfigException('Twig cache path is not writeable');
        }
        $loader = new FilesystemLoader([
            $this->getSourcePath('templates'),
            $this->getSourcePath(),
            $this->getPath('design-system')
        ]);
        $options = ['cache' => $cachePath];
        if ($autoReload) {
            $options['auto_reload'] = true;
        }
        if ($this->config('debug')) {
            $options['debug'] = true;
        }
        $this->twig = new Environment($loader, $options);
        $this->markdown = new Markdown();
        $this->markdown->setTwig($this->twig);
    }

    public function getMarkdown(): Markdown
    {
        return $this->markdown;
    }

    /**
     * Return path relative to root
     * @param string $path
     * @return string
     */
    public function getPath(string $path): string
    {
        return $this->rootPath . '/' . ltrim($path, '/');
    }

    /**
     * Return config value, or null if not set
     * @param string $name
     * @return mixed|null
     */
    public function config(string $name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * Set the source path
     * @param string $sourcePath
     * @throws ConfigException
     */
    public function setSourcePath(string $sourcePath)
    {
        $sourcePath = realpath($sourcePath);
        if (!file_exists($sourcePath)) {
            throw new ConfigException(sprintf('Source path "%s" must exist', $sourcePath));
        }
        $this->sourcePath = $sourcePath;
    }

    /**
     * Return source path
     * @param null $childPath Optional child path to append
     * @return string
     */
    public function getSourcePath($childPath = null): string
    {
        if ($childPath !== null) {
            $childPath = ltrim($childPath, '/');
            $testPath = $this->sourcePath . '/' . $childPath;
            if (!file_exists($testPath)) {
                throw new BuildException(sprintf('Source path "%s" cannot be found', $testPath));
            }
            return $testPath;
        }

        return $this->sourcePath;
    }

    /**
     * Set destination path
     * @param string $destPath
     * @throws ConfigException
     */
    public function setDestPath(string $destPath)
    {
        $destPath = realpath($destPath);
        if (!file_exists($destPath) || !is_writable($destPath)) {
            throw new ConfigException(sprintf('Destination path "%s" must exist and be writable', $sourcePath));
        }
        $this->destPath = $destPath;
    }

    /**
     * Return destination path
     * @param null $childPath Optional child path to append
     * @return string
     */
    public function getDestPath($childPath = null): string
    {
        if ($childPath !== null) {
            $childPath = ltrim($childPath, '/');
            return $this->destPath . '/' . $childPath;
        }

        return $this->destPath;
    }

    /**
     * Delete all files from destination folder, so we can create a clean new set of files
     * @return int Number of files deleted
     */
    public function deleteDestFiles(): int
    {
        $x = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDestPath()));
        $it->rewind();
        while($it->valid()) {
            // Skip file: .gitkeep
            if (!$it->isDot() && $it->getSubPathName() !== '.gitkeep') {
                unlink($it->key());
                $x++;
            }
            $it->next();
        }

        return $x;
    }

    /**
     * Build assets
     * @param string $command Command to run to build assets, relative to project root path
     * @param bool $passthru Whether to output result of command as it runs or not
     * @throws AssetsException
     */
    public function buildAssets(bool $passthru = false)
    {
        $command = $this->config('build_command');

        $command = "cd {$this->rootPath} && " . $command;
        $output = '';

        if ($passthru) {
            passthru($command,$status);
        } else {
            exec($command,$output,$status);
        }

        if ($status !== 0) {
            $message = 'Command: ' . $command;
            if (!$passthru) {
                $message .= PHP_EOL . 'Output: ' . $output;
            }
            throw new AssetsException('Asset build failed. ' . $message);
        }
    }

    /**
     * Build a markdown page into HTML, including front matter
     * @param string $source Source path, relative to source folder
     * @return string Path HTML written to
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function buildMarkdownPage(string $source): string
    {
        $sourcePath = $this->getSourcePath($source);
        if (!file_exists($sourcePath)) {
            throw new BuildException(sprintf('Source file %s cannot be found', $sourcePath));
        }

        // Calculate destination path
        $destination = preg_replace('/\.md$/','.html', $source);

        $html = $this->markdown->parseFile($sourcePath);

        $templateFolder = str_replace($this->getSourcePath(), '', dirname($source));
        $html = $this->markdown->parseSpecialFunctions($html, $templateFolder, dirname($sourcePath), $this->getDestPath($templateFolder));
        $data = [
            'content'   => $html,
        ];

        foreach ($this->markdown->getAllFrontMatter() as $name => $value) {
            $data[$name] = $value;
        }

        $html = $this->twig->render('templates/page.html.twig', $data);
        file_put_contents($this->getDestPath($destination), $html);

        return $this->getDestPath($destination);
    }

    /**
     * Create destination folder
     * @param string $folder
     * @throws BuildException
     */
    public function createDestFolder(string $folder)
    {
        $folder = $this->getDestPath($folder);
        if (is_dir($folder)) {
            return;
        }
        if (!mkdir($folder, 0777, true)) {
            throw new BuildException(sprintf('Cannot create new folder at %s', $folder));
        }
    }

    /**
     * Copy file from source to destination
     * @param string $source Source file, relative to source folder
     * @param string $destFolder Destination folder, if different from source folder
     * @throws BuildException
     */
    public function copyFileToDest(string $source, string $destFolder = null)
    {
        // Calculate destination path
        $source = $this->getSourcePath($source);
        if ($destFolder !== null) {
            $destination = rtrim($destFolder, '/') . '/' . basename($source);
        } else {
            $destination = $this->getDestPath($source);
        }

        if (!copy($source, $destination)) {
            throw new BuildException(sprintf('Cannot copy file to destination %s', $destination));
        }
    }

    /**
     * Render a template and output to destination folder
     * @param string $templatePath Path to template file, relative to source folder
     * @param string $destFolder Folder to save outputted file to, relative to destination folder
     * @throws BuildException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderTemplateToDest(string $templatePath, string $destFolder)
    {
        // Calculate destination path
        $htmlFilename = basename($templatePath, '.twig');
        $destination = $this->getDestPath($htmlFilename);

        $html = $this->twig->render($templatePath);
        if (!file_put_contents($destination, $html)) {
            throw new BuildException(sprintf('Cannot render template file to destination %s', $destination));
        }
    }

    /**
     * Build example HTML templates
     * @return int
     * @throws BuildException
     */
    public function buildExamples(): int
    {
        $x = 0;

        $this->createDestFolder('examples');
        $dir = new \DirectoryIterator($this->getSourcePath('examples'));
        foreach ($dir as $fileinfo) {
            if ($fileinfo->getExtension() === 'twig') {
                $this->renderTemplateToDest('examples/' . $fileinfo->getFilename(), 'examples');
                $x++;
            }
        }
        return $x;
    }

    /**
     * Build markdown files as HTML
     * @return int
     * @throws BuildException
     */
    public function buildPages(): int
    {
        $exclude = [
            'assets',
            'examples',
            'templates'
        ];
        $x = 0;

        $this->verboseInfo('Parsing folder: %s', $this->getSourcePath());
        $sourceDir = new \DirectoryIterator($this->getSourcePath());
        foreach ($sourceDir as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            // Parse Markdown file
            if ($fileInfo->getExtension() === 'md') {
                $this->verboseInfo('Building markdown page: %s', $fileInfo->getFilename());
                $dest = $this->buildMarkdownPage($fileInfo->getFilename());
                $x++;
            }

            // Parse Markdown folder
            if ($fileInfo->isDir() && !in_array($fileInfo->getFilename(), $exclude)) {

                $childDirName = $fileInfo->getFilename();
                $this->createDestFolder($childDirName);

                $this->verboseInfo('Parsing folder: %s', $fileInfo->getPathname());
                $childDir = new \DirectoryIterator($fileInfo->getPathname());
                foreach ($childDir as $childFileInfo) {
                    if ($childFileInfo->isDot()) {
                        continue;
                    }
                    if ($childFileInfo->getExtension() === 'md') {
                        $this->verboseInfo('Building markdown page: %s', $childFileInfo->getFilename());
                        $dest = $this->buildMarkdownPage($childDirName . '/' . $childFileInfo->getFilename());
                        $x++;
                    }
                }

            }
        }

        return $x;
    }

}