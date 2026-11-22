<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Declaration;

use Composer\Composer;
use Composer\Package\PackageInterface;
use RuntimeException;

/** Reads the current extra.webkernel declaration for the root and installed packages. */
final class LCDeclarationReader
{
    /** @var \WeakMap<Composer, list<array{name: string, extra: array<string, mixed>, psr4_prefixes: list<string>}>>|null */
    private static ?\WeakMap $declaration_cache = null;

    /**
     * Reads, validates, and caches root and installed package declarations.
     *
     * @param $composer Composer Composer instance providing root and installed packages.
     * @return list<array{name: string, extra: array<string, mixed>, psr4_prefixes: list<string>}>
     * @throws RuntimeException If a package manifest or declaration is invalid.
     */
    public function read(Composer $composer): array
    {
        if (self::$declaration_cache !== null && isset(self::$declaration_cache[$composer])) {
            return self::$declaration_cache[$composer];
        }

        $root_package = $composer->getPackage();
        $packages = array_merge(
            [$root_package],
            $composer->getRepositoryManager()->getLocalRepository()->getPackages(),
        );
        $declarations = [];
        $seen = [];

        foreach ($packages as $package) {
            if (! $package instanceof PackageInterface) {
                continue;
            }

            $name = $package->getName();
            if (isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $webkernel_extra = $this->readExtra($package, $composer, $package === $root_package);
            (new LCDeclarationValidator())->validate($name, $package->getType(), $webkernel_extra);
            $declarations[] = [
                'name' => $name,
                'extra' => $webkernel_extra,
                'psr4_prefixes' => $this->psr4Prefixes($package),
            ];
        }

        self::$declaration_cache ??= new \WeakMap();
        self::$declaration_cache[$composer] = $declarations;

        return $declarations;
    }

    /** @param $package PackageInterface Composer package. @return list<string> Its PSR-4 namespace prefixes. */
    private function psr4Prefixes(PackageInterface $package): array
    {
        $autoload = $package->getAutoload();
        $prefixes = $autoload['psr-4'] ?? [];

        if (! is_array($prefixes)) {
            return [];
        }

        $prefixes = array_filter(
            array_keys($prefixes),
            static fn (mixed $prefix): bool => is_string($prefix) && $prefix !== '',
        );

        return array_values(array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $prefixes,
        ));
    }

    /**
     * Invalidates cached declarations after Composer changes installed packages.
     * @param $composer Composer Composer instance whose cache entry is removed.
     * @return void
     */
    public static function forget(Composer $composer): void
    {
        if (self::$declaration_cache !== null && isset(self::$declaration_cache[$composer])) {
            unset(self::$declaration_cache[$composer]);
        }
    }

    /**
     * Reads a package's current manifest and extracts extra.webkernel.
     * @param $package PackageInterface Package whose manifest is read.
     * @param $composer Composer Composer instance used to resolve the install path.
     * @param $is_root bool Whether the package is the Composer root package.
     * @return array<string, mixed>
     * @throws RuntimeException If the manifest cannot be read or parsed.
     */
    private function readExtra(PackageInterface $package, Composer $composer, bool $is_root): array
    {
        if ($is_root) {
            return $this->webkernelExtra($package->getExtra(), $package->getName());
        }

        $install_path = $composer->getInstallationManager()->getInstallPath($package);
        $manifest_path = is_string($install_path) ? $install_path . '/composer.json' : null;

        if ($manifest_path === null || ! is_file($manifest_path)) {
            return $this->webkernelExtra($package->getExtra(), $package->getName());
        }

        $contents = file_get_contents($manifest_path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read Composer manifest for package "%s".', $package->getName()));
        }

        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid Composer manifest for package "%s".', $package->getName()), previous: $exception);
        }

        if (! is_array($manifest) || ! is_array($manifest['extra'] ?? null)) {
            return [];
        }

        return $this->webkernelExtra($manifest['extra'], $package->getName());
    }

    /**
     * Extracts the Webkernel declaration from a Composer extra object.
     * @param $extra array<string, mixed> Composer extra values.
     * @param $package_name string Package name used in validation errors.
     * @return array<string, mixed>
     * @throws RuntimeException If extra.webkernel is not an object.
     */
    private function webkernelExtra(array $extra, string $package_name): array
    {
        if (! array_key_exists('webkernel', $extra)) {
            return [];
        }

        $webkernel_extra = $extra['webkernel'];
        if (! is_array($webkernel_extra)) {
            throw new RuntimeException(sprintf(
                '[webkernel/lifecycle] Invalid declaration in package "%s": extra.webkernel must be an object.',
                $package_name,
            ));
        }

        return $webkernel_extra;
    }
}
