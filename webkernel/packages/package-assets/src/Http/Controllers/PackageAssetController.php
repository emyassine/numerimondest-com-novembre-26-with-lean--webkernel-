<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Http\Controllers;

use Composer\InstalledVersions;
use OutOfBoundsException;
use Webkernel\Codebase\Assets\PackageAssetRegistry;

/** Serves explicitly declared files from installed package asset roots. */
final class PackageAssetController
{
    public function show(string $vendor, string $package, string $path): string
    {
        $package_name = $vendor.'/'.$package;
        $resolved_asset = PackageAssetRegistry::resolve($package_name, $path);
        if ($resolved_asset === null) {
            return $this->notFound();
        }

        $mime_types = [
            'css' => 'text/css; charset=UTF-8',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'text/javascript; charset=UTF-8',
            'mjs' => 'text/javascript; charset=UTF-8',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        $extension = strtolower(pathinfo($resolved_asset, PATHINFO_EXTENSION));
        $mime_type = $mime_types[$extension] ?? null;
        if (! is_string($mime_type)) {
            return $this->notFound();
        }
        $modified_at = filemtime($resolved_asset);
        $size = filesize($resolved_asset);
        if (! is_int($modified_at) || ! is_int($size)) {
            return $this->notFound();
        }
        $etag = '"'.hash('sha256', $modified_at.':'.$size.':'.(string) fileinode($resolved_asset)).'"';
        $hashed_name = preg_match('/(?:^|[._-])[a-f0-9]{8,}(?:[._-]|$)/i', basename($resolved_asset)) === 1;
        header('Content-Type: '.$mime_type);
        header('X-Content-Type-Options: nosniff');
        header('ETag: '.$etag);
        header('Cache-Control: public, max-age='.($hashed_name ? '31536000, immutable' : '0, must-revalidate'));
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);

            return '';
        }
        $contents = file_get_contents($resolved_asset);
        if (! is_string($contents)) {
            return $this->notFound();
        }

        return $contents;
    }

    public function branding(string $vendor, string $package, string $asset): string
    {
        $catalog_key = match ($asset) {
            'logo' => 'logo',
            'logo-dark' => 'logo_dark',
            'favicon' => 'favicon',
            'social-image' => 'social_image',
            default => null,
        };
        $package_name = $vendor.'/'.$package;
        $catalog = webkernel_packages()[$package_name]['catalog'] ?? null;
        $relative_path = is_string($catalog_key) && is_array($catalog)
            ? ($catalog['branding'][$catalog_key] ?? null)
            : null;
        if (! is_string($relative_path)) {
            return $this->notFound();
        }
        try {
            $install_path = InstalledVersions::getInstallPath($package_name);
        } catch (OutOfBoundsException) {
            return $this->notFound();
        }
        $package_root = is_string($install_path) ? realpath($install_path) : false;
        $asset_path = is_string($package_root) ? realpath($package_root.'/'.$relative_path) : false;
        if (! is_string($package_root) || ! is_string($asset_path) || ! str_starts_with($asset_path, $package_root.'/') || ! is_file($asset_path)) {
            return $this->notFound();
        }
        $mime_type = mime_content_type($asset_path);
        if (! is_string($mime_type) || ! str_starts_with($mime_type, 'image/')) {
            return $this->notFound();
        }
        $contents = file_get_contents($asset_path);
        if (! is_string($contents)) {
            return $this->notFound();
        }
        header('Content-Type: '.$mime_type);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');

        return $contents;
    }

    private function notFound(): string
    {
        http_response_code(404);

        return '';
    }
}
