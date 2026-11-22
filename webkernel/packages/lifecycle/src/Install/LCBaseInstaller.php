<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Install;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use RuntimeException;

/**
 * Custom installer for webkernel-* package types.
 *
 * @see https://getcomposer.org/doc/articles/custom-installers.md
 */
final class LCBaseInstaller extends LibraryInstaller
{
    private readonly LCInstallerLocations $locations;

    /**
     * Creates the installer using Composer's IO and project configuration.
     * @param $io IOInterface Composer console IO.
     * @param $composer Composer Composer instance whose project is being installed.
     * @return void
     */
    public function __construct(IOInterface $io, Composer $composer)
    {
        parent::__construct($io, $composer, 'library');
        $this->locations = new LCInstallerLocations($composer);
    }

    /**
     * Returns whether this installer handles the supplied package type.
     * @param $packageType string Composer package type.
     * @return bool Whether this installer supports the type.
     */
    public function supports(string $packageType): bool
    {
        return in_array($packageType, $this->locations->types(), strict: true);
    }

    /**
     * Resolves a Webkernel package's install path from its package type.
     * @param $package PackageInterface Package whose install path is resolved.
     * @return string Absolute or Composer-relative install path.
     * @throws RuntimeException If the package declaration is invalid.
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $template = $this->locations->destination($package);

        if ($template === null) {
            return parent::getInstallPath($package);
        }

        [$vendor, $name] = explode('/', $package->getName(), 2);

        $replacements = [
            '{$vendor}' => $vendor,
            '{$name}'   => $name,
        ];

        $type = LCPackageType::from($package->getType());

        if ($type->requiresParentModule()) {
            $parent = $this->locations->parentModule($package);

            if ($parent === null) {
                throw new RuntimeException(sprintf(
                    'Package "%s" (type: "%s") must declare extra.webkernel.module: "vendor/name".',
                    $package->getName(),
                    $type->value,
                ));
            }

            $replacements['{$parentVendor}'] = $parent['vendor'];
            $replacements['{$parentName}']   = $parent['name'];
        }

        return rtrim(strtr($template, $replacements), '/\\');
    }
}
