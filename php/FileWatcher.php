<?php
declare(strict_types=1);

namespace Studio24\Apollo;

use Studio24\Apollo\Exception\FileWatcherException;

class FileWatcher
{
    protected $files = [];

    public function __construct(string $cacheFile)
    {
        $this->loadFromCache($cacheFile);
    }

    /**
     * Load file info from cache
     * @param string $cacheFile
     * @throws FileWatcherException
     */
    public function loadFromCache(string $cacheFile)
    {
        // Create file if doesn't exist
        if (!file_exists($cacheFile)) {
            if (!touch($cacheFile)) {
                throw new FileWatcherException(sprintf('Cache file cannot be created at %s', $cacheFile));
            }
            return;
        }

        $this->files = unserialize(file_get_contents($cacheFile));
        if ($this->files === false || !is_array($this->files)) {
            throw new FileWatcherException(sprintf('Cannot load cache file at %s', $cacheFile));
        }
    }

    /**
     * Save file info to cache
     * @param string $cacheFile
     * @throws FileWatcherException
     */
    public function saveToCache(string $cacheFile)
    {
        $result = file_put_contents($cacheFile, serialize($this->files));
        if ($result === false) {
            throw new FileWatcherException(sprintf('Cannot save cache file to %s', $cacheFile));
        }
    }

    /**
     * Is a file updated, also saves the new modification time
     * @param string $filePath
     * @return bool Whether the file was updated
     * @throws FileWatcherException
     */
    public function isUpdated(string $filePath): bool
    {
        // Check last mod time
        $lastModTime = null;
        if (isset($this->files[$filePath])) {
            $lastModTime = $this->files[$filePath];
        }

        // Check current mod time & save this
        $currentModTime = filemtime($filePath);
        if ($currentModTime === false) {
            throw new FileWatcherException(sprintf('Cannot detect file modification time for %s', $filePath));
        }
        $this->files[$filePath] = $currentModTime;

        // Is updated?
        if ($lastModTime !== null) {
            if ($lastModTime < $currentModTime) {
                return true;
            }
        }

        return false;
    }

}