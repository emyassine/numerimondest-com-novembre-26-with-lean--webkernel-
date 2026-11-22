<?php

namespace Webkernel\Platform\Support\Assets;

use Composer\InstalledVersions;
use Webkernel\Platform\Support\Facades\PlatformAsset;
use LogicException;
use OutOfBoundsException;
use Throwable;

abstract class Asset
{
    protected string $id;

    protected ?string $path = null;

    protected bool $isLoadedOnRequest = false;

    protected ?string $package = null;

    final public function __construct(string $id, ?string $path = null)
    {
        $this->id = $id;
        $this->path = $path;
    }

    public static function make(string $id, ?string $path = null): static
    {
        return app(static::class, ['id' => $id, 'path' => $path]);
    }

    public function loadedOnRequest(bool $condition = true): static
    {
        $this->isLoadedOnRequest = $condition;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function package(?string $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function isRemote(): bool
    {
        return str($this->getPath())->startsWith(['http://', 'https://', '//']);
    }

    public function getUrl(?string $relativePath = null): string
    {
        if ($this->isRemote()) {
            return rtrim((string) $this->getPath(), '/') . ($relativePath === null ? '' : '/' . ltrim($relativePath, '/'));
        }

        $packageUrl = $this->getPackageAssetUrl($relativePath);

        if ($packageUrl !== null) {
            return $packageUrl;
        }

        $publicPath = $this->getRelativePublicPath();
        if ($relativePath !== null) {
            $publicPath = rtrim($publicPath, '/') . '/' . ltrim($relativePath, '/');
        }

        return asset($publicPath);
    }

    protected function getPackageAssetUrl(?string $relativePath = null): ?string
    {
        $package = $this->getPackage();
        $path = $this->getPath();

        if (blank($package) || blank($path)) {
            return null;
        }
        if ($relativePath !== null) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
        }

        try {
            $installPath = InstalledVersions::getInstallPath($package);
        } catch (OutOfBoundsException) {
            return null;
        }

        $packageRoot = is_string($installPath) ? realpath($installPath) : false;
        $assetPath = realpath($path);

        if (
            ! is_string($packageRoot)
            || ! is_string($assetPath)
            || ! str_starts_with($assetPath, rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            || ! is_file($assetPath)
        ) {
            return null;
        }

        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($assetPath, strlen($packageRoot) + 1));
        [$vendor, $name] = explode('/', $package, 2);
        $segments = array_map(rawurlencode(...), explode('/', $relativePath));

        return '/__webkernel-app/assets/' . rawurlencode($vendor) . '/' . rawurlencode($name) . '/' . implode('/', $segments);
    }

    public function getVersion(): string
    {
        $package = $this->getPackage();

        if (blank($package)) {
            return $this->getInstalledVersion('webkernel/platform-support');
        }

        if (
            ($package === 'app') &&
            filled($appVersion = PlatformAsset::getAppVersion())
        ) {
            return $appVersion;
        }

        try {
            return $this->getInstalledVersion($package);
        } catch (Throwable $exception) {
            return $this->getInstalledVersion('webkernel/platform-support');
        }
    }

    protected function getInstalledVersion(string $package): string
    {
        $version = InstalledVersions::getVersion($package);

        if ($version === null) {
            throw new LogicException("Unable to determine the installed version of package [{$package}].");
        }

        if (! $this->isDevVersion($version)) {
            return $version;
        }

        $reference = InstalledVersions::getReference($package);

        if (filled($reference)) {
            return hash('sha256', $reference);
        }

        return $version;
    }

    protected function isDevVersion(string $version): bool
    {
        return str_starts_with($version, 'dev-') || str_ends_with($version, '-dev');
    }

    public function isLoadedOnRequest(): bool
    {
        return $this->isLoadedOnRequest;
    }

    abstract public function getPublicPath(): string;
}
