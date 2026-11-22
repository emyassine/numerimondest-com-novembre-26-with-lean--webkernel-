<?php

namespace Webkernel\Liveview\Compiler;

class CacheManager
{
    public function __construct(
        public string $cacheDirectory,
    ) {}

    public function hasBeenCompiled(string $sourcePath): bool
    {
        $classPath = $this->getClassPath($sourcePath);
        $viewPath = $this->getViewPath($sourcePath);

        return file_exists($classPath) && file_exists($viewPath);
    }

    public function isExpired(string $sourcePath): bool
    {
        $sourceModificationTime = $this->getLatestFileModificationTimeOfSource($sourcePath);

        $compiledModificationTime = $this->getLatestFileModificationTimeOfCompiledFiles($sourcePath);

        return $sourceModificationTime > $compiledModificationTime;
    }

    public function getLatestFileModificationTimeOfSource(string $sourcePath): int
    {
        if (is_dir($sourcePath)) {
            return max(array_map('filemtime', glob($sourcePath . '/*')));
        }

        return filemtime($sourcePath);
    }

    public function getLatestFileModificationTimeOfCompiledFiles(string $sourcePath): int
    {
        $classPath = $this->getClassPath($sourcePath);
        $viewPath = $this->getViewPath($sourcePath);
        $scriptPath = $this->getScriptPath($sourcePath);
        $stylePath = $this->getStylePath($sourcePath);
        $globalStylePath = $this->getGlobalStylePath($sourcePath);

        $times = [];
        foreach ([$classPath, $viewPath, $scriptPath, $stylePath, $globalStylePath] as $path) {
            if (file_exists($path)) {
                $times[] = filemtime($path);
            }
        }

        return empty($times) ? 0 : min($times);
    }

    public function getClassName(string $sourcePath): string
    {
        $instance = require $this->getClassPath($sourcePath);

        return $instance::class;
    }

    public function getHash(string $sourcePath): string
    {
        $basePath = rtrim(base_path(), '/\\');
        $relativePath = str_starts_with($sourcePath, $basePath.DIRECTORY_SEPARATOR)
            ? substr($sourcePath, strlen($basePath))
            : $sourcePath;

        return substr(md5($relativePath), 0, 8);
    }

    public function getClassPath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/classes/' . $hash . '.php';
    }

    public function getViewPath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/views/' . $hash . '.view.php';
    }

    public function getScriptPath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/scripts/' . $hash . '.js';
    }

    public function getStylePath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/styles/' . $hash . '.css';
    }

    public function getGlobalStylePath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/styles/' . $hash . '.global.css';
    }

    public function getPlaceholderPath(string $sourcePath): string
    {
        $hash = $this->getHash($sourcePath);

        return $this->cacheDirectory . '/placeholders/' . $hash . '.view.php';
    }

    public function writeClassFile(string $sourcePath, string $contents): void
    {
        $this->writeAtomically($this->getClassPath($sourcePath), $contents);
    }

    public function writeViewFile(string $sourcePath, string $contents): void
    {
        $viewPath = $this->getViewPath($sourcePath);

        $this->writeAtomically($viewPath, $contents);

        $this->prepareGeneratedFileForCompilation($viewPath);
    }

    public function writeScriptFile(string $sourcePath, string $contents): void
    {
        $scriptPath = $this->getScriptPath($sourcePath);

        $this->writeAtomically($scriptPath, $contents);
    }

    public function writeStyleFile(string $sourcePath, string $contents): void
    {
        $stylePath = $this->getStylePath($sourcePath);

        $this->writeAtomically($stylePath, $contents);
    }

    public function writeGlobalStyleFile(string $sourcePath, string $contents): void
    {
        $stylePath = $this->getGlobalStylePath($sourcePath);

        $this->writeAtomically($stylePath, $contents);
    }

    public function writePlaceholderFile(string $sourcePath, string $contents): void
    {
        $placeholderPath = $this->getPlaceholderPath($sourcePath);

        $this->writeAtomically($placeholderPath, $contents);

        $this->prepareGeneratedFileForCompilation($placeholderPath);
    }

    public function writeIslandFile(string $sourcePath, string $contents): void
    {
        $viewPath = $this->getViewPath($sourcePath);

        $this->writeAtomically($viewPath, $contents);

        $this->prepareGeneratedFileForCompilation($viewPath);
    }

    public function invalidateOpCache(string $sourcePath): void
    {
        // Prevent error in case if `opcache.restrict_api` directive is set
        if (function_exists('opcache_invalidate') && strlen(ini_get('opcache.restrict_api')) < 1) {
            opcache_invalidate($sourcePath, true);
        }
    }

    protected function ensureCacheDirectoryExists(): void
    {
        if (! is_dir($this->cacheDirectory) && ! mkdir($this->cacheDirectory, 0775, true) && ! is_dir($this->cacheDirectory)) {
            throw new \RuntimeException('Unable to create Livewire cache directory: '.$this->cacheDirectory);
        }

        $gitignorePath = $this->cacheDirectory . '/.gitignore';

        if (! file_exists($gitignorePath)) {
            try {
                file_put_contents($gitignorePath, "*\n!.gitignore", LOCK_EX);
            } catch (\Throwable) {
                // Non-critical, ignore if another process created it
            }
        }
    }

    public function prepareGeneratedFileForCompilation(string $path): void
    {
        clearstatcache(true, $path);
        $this->invalidateOpCache($path);
    }

    public function clearCompiledFiles($output = null): void
    {
        try {
            if (is_dir($this->cacheDirectory)) {
                $this->deleteDirectory($this->cacheDirectory);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking view:clear if there's an issue
            // But we can log it if output is available
            if ($output && method_exists($output, 'writeln')) {
                $output->writeln("<comment>Note: Could not clear Livewire compiled files.</comment>");
            }
        }
    }

    private function writeAtomically(string $path, string $contents): void
    {
        $this->ensureCacheDirectoryExists();
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create Livewire cache directory: '.$directory);
        }

        $temporaryPath = tempnam($directory, '.livewire-');
        if (! is_string($temporaryPath)) {
            throw new \RuntimeException('Unable to create a temporary Livewire cache file.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || ! rename($temporaryPath, $path)) {
                throw new \RuntimeException('Unable to publish Livewire cache file: '.$path);
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        clearstatcache(true, $path);
        $this->invalidateOpCache($path);
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $entry) {
            $path = $entry->getPathname();
            if ($entry->isDir() && ! $entry->isLink()) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
