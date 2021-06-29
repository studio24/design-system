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
    private Config $config;
    private string $sourcePath;
    private string $destPath;
    private Environment $twig;
    protected Markdown $markdown;

    /**
     * Initialise Apollo Build
     * @param string $configPath Config to config file, if passed
     * @param bool $autoReload Whether to auto reload Twig templates on file change
     * @throws ConfigException
     */
    public function __construct($autoReload = true)
    {
        $this->config = Config::getInstance();
        $this->setSourcePath($this->config->getConfigPath('source'));
        $this->setDestPath($this->config->getConfigPath('destination'));

        // Twig setup
        $options = [];
        $cachePath = $this->config->getConfigPath('cache_path');
        if (!empty($cachePath) && !is_writable($cachePath)) {
            throw new ConfigException('Twig cache path is not writeable');
        }
        if (!empty($cachePath)) {
            $options = ['cache' => $cachePath];
        }
        if ($autoReload) {
            $options['auto_reload'] = true;
        }
        if ($this->config->get('debug')) {
            $options['debug'] = true;
        }
        $loader = new FilesystemLoader([
            $this->getSourcePath()
        ]);
        $this->twig = new Environment($loader, $options);
        $this->markdown = new Markdown();
        $this->markdown->setTwig($this->twig);
    }

    public function getMarkdown(): Markdown
    {
        return $this->markdown;
    }

    /**
     * Set the source path
     * @param string $sourcePath
     * @throws ConfigException
     */
    public function setSourcePath(string $sourcePath)
    {
        $realSourcePath = realpath($sourcePath);
        if (!$realSourcePath) {
            throw new ConfigException(sprintf('Source path "%s" must exist', $sourcePath));
        }
        $this->sourcePath = $realSourcePath;
    }

    /**
     * Return source path
     * @param null $childPath Optional child path to append
     * @param bool $exists Test whether the path exists
     * @return string
     */
    public function getSourcePath($childPath = null, bool $exists = true): string
    {
        if ($childPath !== null) {
            try {
                $path = $this->config->getRelativePath($this->sourcePath, $childPath, $exists);
                return $path;
            } catch (PathDoesNotExistException $e) {
                throw new BuildException(sprintf('Source path "%s" does not exist', $path));
            }

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
        $realDestPath = realpath($destPath);
        if (!$realDestPath) {
            throw new ConfigException(sprintf('Destination path "%s" must exist', $destPath));
        }
        if (!is_writable($realDestPath)) {
            throw new ConfigException(sprintf('Destination path "%s" must be writable', $destPath));
        }
        $this->destPath = $realDestPath;
    }

    /**
     * Return destination path
     * @param null $childPath Optional child path to append
     * @param bool $exists Test whether the path exists
     * @return string
     */
    public function getDestPath($childPath = null, bool $exists = false): string
    {
        if ($childPath !== null) {
            try {
                $path = $this->config->getRelativePath($this->destPath, $childPath, $exists);
                return $path;
            } catch (PathDoesNotExistException $e) {
                throw new BuildException(sprintf('Destination path "%s" does not exist', $path));
            }
        }
        return $this->destPath;
    }

    /**
     * Delete all files from destination folder, so we can create a clean new set of files
     * @return int Number of files deleted
     */
    public function cleanDestFiles(): int
    {
        $x = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDestPath()));
        $it->rewind();
        while($it->valid()) {
            // Skip file: .gitkeep & .gitignore
            if (!$it->isDot() && !in_array($it->getSubPathName(), $this->config->get('clean_ignore_files'))) {
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
        $command = $this->config->get('build_command');
        if (empty($command)) {
            return;
        }

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

    public function copyDesignSystemAssets()
    {
        // Copy CSS for design system layout
        $this->createDestFolder('assets/design-system/styles/');
        $this->copyFile(__DIR__ . '/../assets/design-system.css', $this->getDestPath('assets/design-system/styles/design-system.css'));
    }

    /**
     * Build a markdown page into HTML, including front matter
     * @param string $source Source path, relative to source folder
     * @return string Config HTML written to
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

        // @todo SpecialFunctions has moved, review this
        //$html = $this->markdown->parseSpecialFunctions($html, $templateFolder, dirname($sourcePath), $this->getDestPath($templateFolder));

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
     * @param string $sourcePath Source file
     * @param string $destination Destination file
     * @throws BuildException
     */
    public function copyFile(string $source, string $destination = null)
    {
        if (!copy($source, $destination)) {
            throw new BuildException(sprintf('Cannot copy source file %s to destination %s', $source, $destination));
        }
    }

    /**
     * Render a template and output to destination folder
     * @param string $templatePath Config to template file, relative to source folder
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