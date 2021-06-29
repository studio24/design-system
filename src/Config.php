<?php

declare(strict_types=1);

namespace Studio24\Apollo;

use Studio24\Apollo\Exception\ConfigException;
use Studio24\Apollo\Exception\PathDoesNotExistException;

class Config
{
    private static ?Config $instance = null;
    private ?string $rootPath = null;

    /**
     * Default config values, can be overridden in a config.php file
     * @var array
     */
    private array $config = [
        'debug'             => false,
        'cache_path'        => null,
        'clean_ignore_files' => ['.gitkeep', '.gitignore'],
        'source'            => 'docs/',
        'destination'       => 'dist/',
        'pages'             => [
            'Get started'   => 'get-started.md',
            'Components'    => 'components/',
            'Examples'      => 'examples/',
        ],
        'assets'            => [

        ],
        'assets_build_command' => null,
    ];

    /**
     * Disable constructor
     */
    private function __construct()
    {
    }

    /**
     * Return singleton instance of config
     * @return Config
     */
    public static function getInstance(?string $rootPath = null, ?string $configPath = null): Config
    {
        if (!(self::$instance instanceof Config)) {
            $config = new Config();

            // Set root path
            if (!empty($rootPath)) {
                $config->setRootPath($rootPath);
            }

            // Load config from --config option, /config.php file if exists, or don't load any config
            if (!empty($configPath)) {
                $config->loadConfig($configPath);
            } elseif ($config->hasRootPath() && file_exists($config->getPath('/config.php'))) {
                $config->loadConfig($config->getPath('/config.php'));
            }

            self::$instance = $config;
        }
        return self::$instance;
    }

    /**
     * Set root path to make all other paths relative to
     * @param string $rootPath
     * @throws PathDoesNotExistException
     */
    public function setRootPath(string $rootPath)
    {
        $realRootPath = realpath($rootPath);
        if (!$realRootPath) {
            throw new PathDoesNotExistException(sprintf('Root path "%s" must exist', $rootPath));
        }
        $this->rootPath = $rootPath;
    }

    /**
     * Whether a root path is set
     * @return bool
     */
    public function hasRootPath(): bool
    {
        return ($this->rootPath !== null);
    }

    /**
     * Load config file and override default config values
     * @param string $configPath
     * @throws ConfigException
     * @throws PathDoesNotExistException
     */
    public function loadConfig(string $configPath)
    {
        if (file_exists($configPath)) {
            require $configPath;
        } elseif ($this->hasRootPath() && file_exists($this->getPath($configPath))) {
            require $this->getPath($configPath);
        } else {
            throw new PathDoesNotExistException(sprintf('Config file does not exist at %s', $configPath));
        }
        if (!isset($config) || !is_array($config)) {
            throw new ConfigException('Config file must contain the $config variable and it must be an array');
        }
        foreach ($this->config as $name => $value) {
            if (isset($config[$name])) {
                $this->config[$name] = $config[$name];
            }
        }
    }

    /**
     * Append two file paths together and optionally test if the path exists
     * @param string $parentPath
     * @param string $childPath
     * @param bool $exists Test whether the path exists
     * @return string
     * @throws PathDoesNotExistException
     */
    public function getRelativePath(string $parentPath, string $childPath, bool $exists = false): string
    {
        $path = rtrim($parentPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($childPath, DIRECTORY_SEPARATOR);
        if ($exists && !file_exists($path)) {
            throw new PathDoesNotExistException(sprintf('Path "%s" does not exist', $path));
        }
        return $path;
    }

    /**
     * Return path relative to root path
     * @param string $childPath
     * @param bool $exists Test whether the path exists
     * @return string
     * @throws PathDoesNotExistException
     */
    public function getPath(string $childPath, bool $exists = false): string
    {
        return $this->getRelativePath($this->rootPath, $childPath, $exists);
    }

    /**
     * Return config option relative to root path
     * @param string $name
     * @param bool $exists Test whether the path exists
     * @return string|null
     * @throws PathDoesNotExistException
     */
    public function getConfigPath(string $name, bool $exists = false): ?string
    {
        if (isset($this->config[$name])) {
            return $this->getPath($this->config[$name], $exists);
        }
        return null;
    }

    /**
     * Return config option
     * @param string $name
     * @return mixed|null
     */
    public function get(string $name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

}