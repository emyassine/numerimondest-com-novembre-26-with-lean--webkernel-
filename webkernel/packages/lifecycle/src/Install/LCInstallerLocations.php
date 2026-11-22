<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Install;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use RuntimeException;

/**
 * Maps LCPackageType → install path template.
 *
 * Variables: {$vendor}, {$name}, {$parentVendor}, {$parentName}
 */
final class LCInstallerLocations
{
    /** @var array<string, list<LCPackageType>>|null */
    private ?array $templates = null;

    public function __construct(private readonly Composer $composer) {}

    /**
     * Builds install path templates relative to the Composer project and vendor directory.
     * @return array<string, list<LCPackageType>>
     */
    private function templates(): array
    {
        if ($this->templates !== null) {
            return $this->templates;
        }

        $filesystem = new Filesystem();
        $root_manifest = $this->composer->getConfig()->getConfigSource()->getName();
        $project_dir = realpath(dirname($root_manifest));
        $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
        if (! is_string($project_dir) || ! is_string($vendor_dir) || $vendor_dir === '') {
            throw new RuntimeException('Composer project and vendor paths could not be resolved.');
        }
        if (! str_starts_with($vendor_dir, DIRECTORY_SEPARATOR) && preg_match('/\A[A-Za-z]:[\\\\\/]/', $vendor_dir) !== 1) {
            $vendor_dir = $project_dir.'/'.$vendor_dir;
        }
        $vendor_dir = rtrim($filesystem->normalizePath($vendor_dir), '/');
        $project_dir = rtrim($filesystem->normalizePath($project_dir), '/');

        return $this->templates = [
            $project_dir . '/modules/{$vendor}/{$name}' => [
                LCPackageType::BusinessModule,
                LCPackageType::PlatformModule,
            ],
            $project_dir . '/modules/{$parentVendor}/{$parentName}/features/{$vendor}-{$name}' => [
                LCPackageType::BusinessModuleFeature,
                LCPackageType::PlatformModuleFeature,
            ],
            $project_dir . '/ffi/{$vendor}/{$name}' => [
                LCPackageType::Ffi,
            ],
            $vendor_dir . '/{$vendor}/{$name}' => [
                LCPackageType::Assets,
                LCPackageType::Component,
                LCPackageType::DevTool,
                LCPackageType::Stdlib,
                LCPackageType::Element,
                LCPackageType::Agent,
            ],
        ];
    }

    /**
     * Returns every package type supported by the lifecycle installer.
     * @return list<string>
     */
    public function types(): array
    {
        return array_map(static fn (LCPackageType $t): string => $t->value, LCPackageType::cases());
    }

    /**
     * Returns the install path template for a package, or null for standard vendor placement.
     * @param $package PackageInterface Composer package to locate.
     * @return string|null Install path template or null for Composer's default.
     */
    public function destination(PackageInterface $package): ?string
    {
        $type = LCPackageType::tryFrom($package->getType());

        if ($type === null) {
            return null;
        }

        foreach ($this->templates() as $template => $types) {
            if (in_array($type, $types, strict: true)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Resolves and parses the declared parent module package name.
     * @param $package PackageInterface Feature package declaring its parent module.
     * @return array{vendor: string, name: string}|null
     */
    public function parentModule(PackageInterface $package): ?array
    {
        $module = $package->getExtra()['webkernel']['module'] ?? null;

        if (! is_string($module) || preg_match('/^[a-z0-9_-]+\/[a-z0-9_-]+$/D', $module) !== 1) {
            return null;
        }

        [$vendor, $name] = explode('/', $module, 2);

        return ['vendor' => $vendor, 'name' => $name];
    }
}
