<?php

declare(strict_types=1);

namespace Studio24\DesignSystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Studio24\DesignSystem\Exception\ConfigException;
use Studio24\DesignSystem\Exception\CreateFileException;
use Studio24\DesignSystem\Exception\PathDoesNotExistException;

class Config
{
    const DEFAULT_CONFIG_FILE = 'design-system-config.php';
    const DEFAULT_ASSETS_BUILD_SCRIPT = 'design-system-build.sh';
    const DIST_PATH = '_dist';
    const CODE_PATH = self::DIST_PATH . '/code';
    const ASSETS_PATH = self::DIST_PATH . '/assets';

    private string $rootPath;
    private Filesystem $filesystem;

    /**
     * Default config values, can be overridden in a config.php file
     * @var array
     */
    private array $config = [
        'navigation'        => [
            'Home'          => '/',
            'Templates'     => '/code/templates/',
        ],
        'assets_build_command' => './' . self::DEFAULT_ASSETS_BUILD_SCRIPT,
        'docs_path'         => 'docs/',
        'templates_path'    => 'templates/',
        'zip_name'          => null,
        'zip_folder'        => null,
    ];

    /**
     * Constructor
     *
     * @param string $rootPath Root path to run design system build process from
     * @param string|null $configPath Path to config file, relative to root
     * @throws ConfigException
     * @throws PathDoesNotExistException
     * @throws \League\Flysystem\FilesystemException
     */
    public function __construct(string $rootPath, string $configPath = null)
    {
        $this->setRootPath($rootPath);
        $adapter = new LocalFilesystemAdapter($rootPath);
        $this->filesystem = new Filesystem($adapter);

        if ($configPath !== null) {
            $this->loadConfig($configPath);
        }
    }

    /**
     * Return array of primary navigation
     *
     * @param ?string $currentUrl
     * @return array
     */
    public function getNavigation(string $currentUrl = null): array
    {
        $navigation = [];
        foreach ($this->get('navigation') as $label => $url) {
            $active = false;
            if ($url === '/') {
                // Only mark homepage as active for homepage
                if ($currentUrl === '/') {
                    $active = true;
                }
            } else {
                // Mark other sections as active for any child page
                $active = preg_match('!^' . preg_quote($url, '!') . '!', $currentUrl);
            }
            $navigation[] = [
                'label'  => $label,
                'url'    => $url,
                'active' => $active
            ];
        }
        return $navigation;
    }

    /**
     * Does the URL appear in the top navigation?
     * @param string $url
     * @return bool
     * @throws ConfigException
     */
    public function inNavigation(string $url): bool
    {
        $url = preg_replace('/index\.html$/', '', $url);
        return in_array($url, $this->get('navigation'));
    }

    /**
     * Set root path to make all other paths relative to
     * @param string $rootPath
     */
    public function setRootPath(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @return string|null
     */
    public function getRootPath(): ?string
    {
        return $this->rootPath;
    }

    /**
     * Load config file and override default config values
     * @param string $configPath
     * @throws ConfigException
     * @throws PathDoesNotExistException
     */
    public function loadConfig(string $configPath)
    {
        if (!$this->filesystem->fileExists($configPath)) {
            throw new PathDoesNotExistException(sprintf('Config file does not exist at %s', $configPath));
        }

        // Require config file, which must contain a $config array
        require $configPath;
        if (!isset($config) || !is_array($config)) {
            throw new ConfigException(sprintf('Config file %s must contain the $config variable and it must be an array', $configPath));
        }
        foreach ($this->config as $name => $value) {
            if (isset($config[$name])) {
                $this->config[$name] = $config[$name];
            }
        }
    }

    /**
     * Convert Twig template name into an HTML filename
     * @param string $filepath Path to Twig file or filename
     * @return string Filename only, ending .html
     */
    public function getHtmlFilename(string $filepath): string
    {
        $filepath = basename($filepath, '.twig');
        $filepath = basename($filepath, '.html');
        return $filepath . '.html';
    }

    /**
     * Append two file paths together
     * @param string $parentPath
     * @param string $childPath
     * @return string
     */
    public function buildPath(string $parentPath, string $childPath): string
    {
        return rtrim($parentPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($childPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Return full path for config property, including root path
     * @param string $configNameOrFilename If config property, return full path for this property, or append filename string to root path
     * @return string
     */
    public function getFullPath(string $configNameOrFilename): string
    {
        if ($this->has($configNameOrFilename)) {
            return $this->buildPath($this->getRootPath(), $this->get($configNameOrFilename));
        } else {
            return $this->buildPath($this->getRootPath(), $configNameOrFilename);
        }
    }

    /**
     * Return URL based on filepath in dist folder
     * @param string $filename
     * @return string
     */
    public function getDistUrl(string $filename): string
    {
        $preg = '!^' . preg_quote(self::DIST_PATH) . '!';
        $url = preg_replace($preg, '', $filename);
        $url = preg_replace('!/index\.html$!', '/', $url);
        return $url;
    }

    /**
     * Whether config property exists
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /**
     * Return config option
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        if (!$this->has($name)) {
            throw new ConfigException(sprintf('Config name %s not found', $name));
        }
        return $this->config[$name];
    }

    /**
     * Save a copy of the default config file to path, if it does not exist
     * @param string $absolutePath Full path to save file to
     * @return bool Whether file was created
     * @throws ConfigException
     */
    public static function saveDefaultConfigFile(string $absolutePath): bool
    {
        if (file_exists($absolutePath)) {
            return false;
        }
        if (!copy(__DIR__ . '/../templates/config/design-system-config.php', $absolutePath)) {
            throw new CreateFileException(sprintf('Cannot save default config file at path %s', $absolutePath));
        }
        return true;
    }

    /**
     * Save build assets bash script to path, if it does not exist
     * @param string $absolutePath
     * @return bool Whether file was created
     * @throws ConfigException
     */
    public static function saveBuildAssetsFile(string $absolutePath): bool
    {
        if (file_exists($absolutePath)) {
            return false;
        }
        if (!copy(__DIR__ . '/../templates/config/design-system-build.sh', $absolutePath)) {
            throw new CreateFileException(sprintf('Cannot save build assets file at path %s', $absolutePath));
        }
        chmod($absolutePath, 0755);
        return true;
    }

}