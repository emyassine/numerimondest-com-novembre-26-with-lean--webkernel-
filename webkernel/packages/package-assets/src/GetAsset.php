<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Getters;

use InvalidArgumentException;
use Webkernel\Codebase\Assets\PackageAssetRegistry;

/** Builds a public URL for a file declared by an installed package. */
final class GetAsset
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private static array $vite = [];

    /** @var array<string, string> */
    private static array $asset_versions = [];

    private static bool $vite_loaded = false;

    private function __construct() {}

    /** Seeds the Vite declarations when they are loaded during application boot. @param $manifest array<string, array<string, array<string, mixed>>> */
    public static function boot(array $manifest): void
    {
        self::$vite = $manifest;
        self::$vite_loaded = true;
    }

    public static function beginRequest(): void
    {
        self::$vite = [];
        self::$vite_loaded = false;
        self::$asset_versions = [];
    }

    /** @return array{js: string, css: list<string>} */
    public static function vite(string $package_name, string $entry): array
    {
        self::loadViteManifest();
        $declaration = self::$vite[$package_name][$entry] ?? null;
        if (! is_array($declaration) || ! is_string($declaration['file'] ?? null)) {
            throw new InvalidArgumentException('The requested Vite entry is not declared.');
        }
        $css = $declaration['css'] ?? [];
        if (! is_array($css) || ! array_is_list($css)) {
            throw new InvalidArgumentException('The requested Vite entry has an invalid stylesheet declaration.');
        }
        foreach ($css as $file) {
            if (! is_string($file)) {
                throw new InvalidArgumentException('The requested Vite entry has an invalid stylesheet declaration.');
            }
        }

        return [
            'js' => self::url($package_name, 'dist/'.$declaration['file']),
            'css' => array_map(static fn (string $file): string => self::url($package_name, 'dist/'.$file), $css),
        ];
    }

    /** @return list<string> */
    public static function viteStyles(string $package_name, string $entry): array
    {
        self::loadViteManifest();
        $declaration = self::$vite[$package_name][$entry] ?? null;
        if (! is_array($declaration)) {
            throw new InvalidArgumentException('The requested Vite entry is not declared.');
        }

        $stylesheets = $declaration['css'] ?? [];
        if (! is_array($stylesheets) || ! array_is_list($stylesheets)) {
            throw new InvalidArgumentException('The requested Vite entry has an invalid stylesheet declaration.');
        }
        foreach ($stylesheets as $stylesheet) {
            if (! is_string($stylesheet)) {
                throw new InvalidArgumentException('The requested Vite entry has an invalid stylesheet declaration.');
            }
        }

        $file = $declaration['file'] ?? null;
        if (! is_string($file)) {
            throw new InvalidArgumentException('The requested Vite entry is not declared.');
        }
        if (str_ends_with($file, '.css')) {
            $stylesheets[] = $file;
        }

        return array_map(
            static fn (string $file): string => self::url($package_name, 'dist/'.$file),
            array_values(array_unique($stylesheets)),
        );
    }

    private static function loadViteManifest(): void
    {
        if (self::$vite_loaded) {
            return;
        }
        self::$vite_loaded = true;

        $manifest_path = base_path().'/runtime/cache/declarations/webkernel_vite.php';
        if (! is_file($manifest_path)) {
            return;
        }

        $manifest = require $manifest_path;
        if (is_array($manifest)) {
            self::$vite = $manifest;
        }
    }

    /**
     * Returns an asset URL using its Composer package name and package-relative path.
     *
     * Example: GetAsset::url('webkernel/webkernel', 'app/resources/assets/branding/favicon.png')
     *          returns a content-versioned package asset URL.
     *
     * @param $package_name string Full Composer name, such as `vendor/package`.
     * @param $package_relative_path string File path relative to the package root.
     * @return string Public URL for the declared asset.
     * @throws InvalidArgumentException When the package or path is invalid or undeclared.
     */
    public static function url(string $package_name, string $package_relative_path): string
    {
        if (
            preg_match('~\A[a-z0-9_.-]+/[a-z0-9_.-]+\z~i', $package_name) !== 1
            || ! PackageAssetRegistry::isDeclared($package_name, $package_relative_path)
        ) {
            throw new InvalidArgumentException('The requested file is not a declared package asset.');
        }

        $segments = explode('/', $package_name.'/'.$package_relative_path);
        $url = '/__webkernel-app/assets/'.implode('/', array_map(rawurlencode(...), $segments));
        $key = $package_name.'/'.$package_relative_path;
        $version = self::$asset_versions[$key] ?? null;
        if ($version === null) {
            $asset_path = PackageAssetRegistry::resolve($package_name, $package_relative_path);
            $digest = is_string($asset_path) ? @hash_file('sha256', $asset_path) : false;
            $version = is_string($digest) ? substr($digest, 0, 16) : '';
            self::$asset_versions[$key] = $version;
        }

        return $version === '' ? $url : $url.'?v='.$version;
    }
}
