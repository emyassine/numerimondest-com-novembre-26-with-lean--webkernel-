<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Deployment;

use RuntimeException;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;
use Webkernel\ServiceProvider\Deployment\ArtifactPublisher;

/** Stages declared package assets and atomically copies them into public/ on deploy. */
final class PackagePublicAssetsBuilder implements ArtifactBuilder, ArtifactPublisher
{
    private const KEY = 'public-assets';

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'css' => 'css', 'gif' => 'gif', 'ico' => 'ico', 'jpeg' => 'jpeg', 'jpg' => 'jpg',
        'js' => 'js', 'mjs' => 'mjs', 'png' => 'png', 'svg' => 'svg', 'webp' => 'webp',
        'woff' => 'woff', 'woff2' => 'woff2',
    ];

    public function artifactKey(): string
    {
        return self::KEY;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function build(ArtifactBuildContext $context): void
    {
        $source_manifest = $context->rootPath.'/runtime/cache/declarations/webkernel_asset_roots.php';
        $packages = is_file($source_manifest) ? require $source_manifest : [];
        if (! is_array($packages)) {
            throw new RuntimeException('The compiled package asset roots manifest is invalid.');
        }

        $target_root = $context->artifactPath(self::KEY);
        foreach ($packages as $package_name => $declaration) {
            if (! is_string($package_name) || preg_match('/\A[a-z0-9_.-]+\/[a-z0-9_.-]+\z/i', $package_name) !== 1 || ! is_array($declaration)) {
                continue;
            }
            $package_root = realpath((string) ($declaration['package_root'] ?? ''));
            $roots = $declaration['roots'] ?? [];
            if (! is_string($package_root) || ! is_array($roots)) {
                continue;
            }
            [$vendor, $package] = explode('/', $package_name, 2);
            foreach ($roots as $declared_root) {
                $source_root = is_string($declared_root) ? realpath($declared_root) : false;
                if (! is_string($source_root) || ! str_starts_with($source_root, $package_root.'/') || ! is_dir($source_root)) {
                    continue;
                }
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($source_root, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY,
                );
                foreach ($iterator as $file) {
                    if (! $file->isFile() || $file->isLink()) {
                        continue;
                    }
                    $extension = strtolower($file->getExtension());
                    if (! isset(self::MIME_EXTENSIONS[$extension])) {
                        continue;
                    }
                    $relative_path = substr($file->getPathname(), strlen($package_root) + 1);
                    if (! is_string($relative_path) || $relative_path === '' || in_array('..', explode('/', $relative_path), true)) {
                        continue;
                    }
                    $destination = $target_root.'/__webkernel-app/assets/'.$vendor.'/'.$package.'/'.$relative_path;
                    $directory = dirname($destination);
                    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                        throw new RuntimeException('Unable to stage public package assets.');
                    }
                    if (! copy($file->getPathname(), $destination)) {
                        throw new RuntimeException('Unable to stage public package asset: '.$relative_path);
                    }
                }
            }
        }
    }

    public function publish(ArtifactBuildContext $context): void
    {
        $source_root = $context->generationPath.'/'.self::KEY;
        $public_root = $context->rootPath.'/public';
        if (! is_dir($public_root) && ! mkdir($public_root, 0775, true) && ! is_dir($public_root)) {
            throw new RuntimeException('Unable to create the public directory for deployment assets.');
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source_root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative_path = substr($file->getPathname(), strlen($source_root) + 1);
            if (! is_string($relative_path) || str_starts_with($relative_path, '../') || str_contains($relative_path, '/../')) {
                throw new RuntimeException('The public asset artifact contains an unsafe path.');
            }
            $destination = $public_root.'/'.$relative_path;
            $directory = dirname($destination);
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Unable to create a public asset directory.');
            }
            $temporary = $destination.'.deploy-'.bin2hex(random_bytes(6));
            if (! copy($file->getPathname(), $temporary) || ! rename($temporary, $destination)) {
                @unlink($temporary);
                throw new RuntimeException('Unable to atomically publish public asset: '.$relative_path);
            }
        }
    }
}
