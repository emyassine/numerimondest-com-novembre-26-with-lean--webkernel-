<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
//>
//> IDE-only Composer API declarations for static analysis. Composer core APIs
//> are provided by the Composer process and are not indexed from the app source.

namespace Composer;

/** IDE declaration of Composer's array repository type. */
class ArrayRepository
{
    /** @return array<Package\PackageInterface> */
    public function getPackages(): array
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of Composer's library installer type. */
class LibraryInstaller
{
    /**
     * @param $io IO\IOInterface Composer console IO.
     * @param $composer Composer Composer instance.
     * @param $type string|null Package type handled by the installer.
     */
    public function __construct(IO\IOInterface $io, Composer $composer, ?string $type = 'library') {}

    /** @param $package_type string @return bool */
    public function supports(string $package_type): bool
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @param $package Package\PackageInterface @return string */
    public function getInstallPath(Package\PackageInterface $package): string
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of Composer's dependency operation type. */
interface OperationInterface {}

/** IDE declaration of Composer's application container. */
class Composer
{
    /**
     * @return Installer\InstallationManager
     * @throws \LogicException
     */
    public function getInstallationManager(): Installer\InstallationManager
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return Config
     * @throws \LogicException
     */
    public function getConfig(): Config
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return Package\PackageInterface
     * @throws \LogicException
     */
    public function getPackage(): Package\PackageInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return Repository\RepositoryManager
     * @throws \LogicException
     */
    public function getRepositoryManager(): Repository\RepositoryManager
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of Composer runtime package metadata. */
class InstalledVersions
{
    /**
     * @param $packageName string
     * @return bool
     */
    public static function isInstalled(string $packageName, bool $includeDevRequirements = true): bool
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @param $packageName string
     * @return string|null
     * @throws \OutOfBoundsException
     */
    public static function getInstallPath(string $packageName): ?string
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return array<string, mixed>
     * @throws \LogicException
     */
    public static function getRootPackage(): array
    {
        throw new \LogicException('IDE-only declaration.');
    }

}

/** IDE declaration of Composer configuration access. */
class Config
{
    /**
     * @param $config array<string, mixed>
     * @param $source string
     * @return void
     */
    public function merge(array $config, string $source = 'unknown'): void
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @param $name string
     * @return mixed
     */
    public function get(string $name): mixed
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return Config\ConfigSourceInterface
     * @throws \LogicException
     */
    public function getConfigSource(): Config\ConfigSourceInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

namespace Composer\Config;

/** IDE declaration of Composer's configuration source contract. */
interface ConfigSourceInterface
{
    /** @return string */
    public function getName(): string;
}

namespace Composer\Installer;

/** IDE declaration of Composer's library installer. */
class LibraryInstaller
{
    /**
     * @param $io \Composer\IO\IOInterface
     * @param $composer \Composer\Composer
     * @param $type string|null
     * @return void
     */
    public function __construct(\Composer\IO\IOInterface $io, \Composer\Composer $composer, ?string $type = 'library') {}

    /**
     * @param $packageType string
     * @return bool
     */
    public function supports(string $packageType): bool
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @param $package \Composer\Package\PackageInterface
     * @return string
     * @throws \LogicException
     */
    public function getInstallPath(\Composer\Package\PackageInterface $package): string
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of Composer's package installation manager. */
class InstallationManager
{
    /**
     * @param $package \Composer\Package\PackageInterface
     * @return string|null
     */
    public function getInstallPath(\Composer\Package\PackageInterface $package): ?string
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @param $installer LibraryInstaller
     * @return void
     */
    public function addInstaller(LibraryInstaller $installer): void {}
}

namespace Composer\IO;

/** IDE declaration of Composer's console IO contract. */
interface IOInterface
{
    public const QUIET = 16;
    public const NORMAL = 32;
    public const VERBOSE = 64;
    public const VERY_VERBOSE = 128;
    public const DEBUG = 256;

    /**
     * @param $messages string|array<string>
     * @param $newline bool
     * @param $verbosity int
     * @return void
     */
    public function write(string|array $messages, bool $newline = true, int $verbosity = self::NORMAL): void;

    /**
     * @param $messages string|array<string>
     * @param $newline bool
     * @param $verbosity int
     * @return void
     */
    public function writeError(string|array $messages, bool $newline = true, int $verbosity = self::NORMAL): void;
}

namespace Composer\Package;

/** IDE declaration of Composer package metadata contract. */
interface PackageInterface
{
    /** @return string */
    public function getName(): string;

    /** @return string */
    public function getType(): string;

    /** @return array<string, mixed> */
    public function getExtra(): array;
}

namespace Composer\Plugin;

/** IDE declaration of Composer plugin lifecycle contract. */
interface PluginInterface
{
    /**
     * @param $composer \Composer\Composer
     * @param $io \Composer\IO\IOInterface
     * @return void
     */
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;

    /**
     * @param $composer \Composer\Composer
     * @param $io \Composer\IO\IOInterface
     * @return void
     */
    public function deactivate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;

    /**
     * @param $composer \Composer\Composer
     * @param $io \Composer\IO\IOInterface
     * @return void
     */
    public function uninstall(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;
}

namespace Composer\EventDispatcher;

/** IDE declaration of Composer event subscriber contract. */
interface EventSubscriberInterface
{
    /** @return array<string, mixed> */
    public static function getSubscribedEvents(): array;
}

namespace Composer\Script;

/** IDE declaration of a Composer script event. */
class Event
{
    /**
     * @return \Composer\Composer
     * @throws \LogicException
     */
    public function getComposer(): \Composer\Composer
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return \Composer\IO\IOInterface
     * @throws \LogicException
     */
    public function getIO(): \Composer\IO\IOInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /**
     * @return string
     * @throws \LogicException
     */
    public function getName(): string
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

namespace Composer\Installer;

/** IDE declaration of a Composer package operation event. */
class PackageEvent
{
    /** @return \Composer\Composer */
    public function getComposer(): \Composer\Composer
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @return \Composer\IO\IOInterface */
    public function getIO(): \Composer\IO\IOInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @return string */
    public function getName(): string
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @return \Composer\DependencyResolver\Operation\OperationInterface */
    public function getOperation(): \Composer\DependencyResolver\Operation\OperationInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

namespace Composer\DependencyResolver\Operation;

/** IDE declaration of Composer dependency operation contract. */
interface OperationInterface {}

/** IDE declaration of a Composer package install operation. */
class InstallOperation implements OperationInterface
{
    /** @return \Composer\Package\PackageInterface */
    public function getPackage(): \Composer\Package\PackageInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of a Composer package uninstall operation. */
class UninstallOperation implements OperationInterface
{
    /** @return \Composer\Package\PackageInterface */
    public function getPackage(): \Composer\Package\PackageInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of a Composer package update operation. */
class UpdateOperation implements OperationInterface
{
    /** @return \Composer\Package\PackageInterface */
    public function getInitialPackage(): \Composer\Package\PackageInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @return \Composer\Package\PackageInterface */
    public function getTargetPackage(): \Composer\Package\PackageInterface
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

namespace Composer\Repository;

/** IDE declaration of Composer repository access. */
class RepositoryManager
{
    /**
     * @return ArrayRepository
     * @throws \LogicException
     */
    public function getLocalRepository(): ArrayRepository
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

/** IDE declaration of Composer's in-memory package repository. */
class ArrayRepository
{
    /**
     * @return array<\Composer\Package\PackageInterface>
     * @throws \LogicException
     */
    public function getPackages(): array
    {
        throw new \LogicException('IDE-only declaration.');
    }
}

namespace Composer\Util;

/** IDE declaration of Composer filesystem utilities. */
class Filesystem
{
    /** @param $path string @return bool */
    public function isAbsolutePath(string $path): bool
    {
        throw new \LogicException('IDE-only declaration.');
    }

    /** @param $path string @return string */
    public function normalizePath(string $path): string
    {
        throw new \LogicException('IDE-only declaration.');
    }
}
