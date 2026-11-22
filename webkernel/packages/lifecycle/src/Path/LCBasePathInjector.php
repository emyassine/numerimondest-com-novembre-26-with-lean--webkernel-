<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Path;

use Composer\InstalledVersions;
use Composer\Composer;
use RuntimeException;

/** Injects Composer's current root package path into the process. */
final class LCBasePathInjector
{
    /**
     * Resolves the Composer root package path and stores it in the process.
     * @return void
     * @throws RuntimeException If Composer cannot resolve the root package path.
     */
    public static function inject(?Composer $composer = null): void
    {
        $root_path = $composer !== null
            ? self::rootPathFromComposer($composer)
            : (InstalledVersions::getRootPackage()['install_path'] ?? null);
        $base_path = is_string($root_path) ? realpath($root_path) : false;

        if ($base_path === false) {
            throw new RuntimeException('Composer could not resolve the root package installation path.');
        }

        if (($GLOBALS['BASE_PATH'] ?? null) !== $base_path) {
            $GLOBALS['BASE_PATH'] = $base_path;
        }
    }

    /** Resolves the project root from Composer's root configuration file. */
    private static function rootPathFromComposer(Composer $composer): ?string
    {
        return dirname($composer->getConfig()->getConfigSource()->getName());
    }
}
