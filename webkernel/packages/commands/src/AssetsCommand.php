<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands;

use Composer\InstalledVersions;
use Webkernel\Config\Config;
use Webkernel\Console\Attribute\ConsoleCommand;

/** Installs and builds frontend assets for workspace packages. */
final class AssetsCommand
{
    /** @param $package string @param $arguments list<string> @return int */
    #[ConsoleCommand(name: 'assets:build', description: 'Build CSS and JavaScript for a package, webkernel/*, or all packages')]
    public function build(string $package = 'all', string ...$arguments): int
    {
        return $this->run('build', $package, $arguments);
    }

    /** @param $package string @param $arguments list<string> @return int */
    #[ConsoleCommand(name: 'assets:install', description: 'Install locked frontend dependencies for selected packages')]
    public function install(string $package = 'all', string ...$arguments): int
    {
        return $this->run('install', $package, $arguments);
    }

    /** @param $package string @param $arguments list<string> @return int */
    #[ConsoleCommand(name: 'assets:sync', description: 'Install locked dependencies, build assets, and refresh Composer asset manifests')]
    public function sync(string $package = 'all', string ...$arguments): int
    {
        return $this->run('sync', $package, $arguments);
    }

    /** @param $action 'build'|'install'|'sync' @param $package string @param $arguments list<string> @return int */
    private function run(string $action, string $package, array $arguments): int
    {
        $packages = $this->selectPackages($package, $arguments);
        if ($packages === null) {
            return 2;
        }

        $manager = $this->packageManager();
        if ($manager === null) {
            return 127;
        }

        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['TMPDIR'] = tmp_path();

        if ($action === 'install' || $action === 'sync') {
            $result = $this->installPackages($packages, $manager, $environment);
            if ($result !== 0 || $action === 'install') {
                return $result;
            }
        }

        $result = $this->buildPackages($packages, $manager, $environment);
        if ($result !== 0) {
            return $result;
        }

        console()->info('Refreshing the application asset manifest...');

        $composer = self::binaryPath('composer');
        if ($composer === null) {
            console()->error('Composer was not found on PATH.');

            return 127;
        }

        $result = self::runProcess([$composer, 'dump-autoload'], base_path(), $environment);
        if ($result !== 0) {
            return $result;
        }

        return $this->publishDeclaredAssets($packages);
    }

    /** @return array<string, mixed> */
    private function declaredAssetBuilds(): array
    {
        $builds = config('site.asset_builds', []);

        return is_array($builds) ? $builds : [];
    }

    /** @param array<string, string> $built_packages */
    private function publishDeclaredAssets(array $built_packages): int
    {
        foreach ($this->declaredAssetBuilds() as $package_name => $declaration) {
            if (! is_array($declaration)) {
                console()->error('Invalid asset build declaration for '.$package_name.'.');

                return 2;
            }
            $source = $declaration['source'] ?? null;
            $outputs = $declaration['outputs'] ?? null;
            if (! is_string($source) || ! is_array($outputs)) {
                console()->error('Invalid asset build declaration for '.$package_name.'.');

                return 2;
            }
            $package_path = realpath(base_path($source));
            if (! is_string($package_path)) {
                foreach ($outputs as $destination) {
                    if (! is_string($destination) || ! is_file(base_path($destination))) {
                        console()->error('Asset source '.$package_name.' is unavailable and its declared output is missing.');

                        return 1;
                    }
                }
                continue;
            }
            if (! isset($built_packages[$package_name])) {
                foreach ($outputs as $destination) {
                    if (! is_string($destination) || ! is_file(base_path($destination))) {
                        console()->error('Asset output '.$package_name.' is missing; run assets:sync all.');

                        return 1;
                    }
                }
                continue;
            }
            foreach ($outputs as $output => $destination) {
                if (
                    ! is_string($output)
                    || ! is_string($destination)
                    || str_starts_with($output, '/')
                    || str_starts_with($destination, '/')
                    || str_contains($output, '..')
                    || str_contains($destination, '..')
                ) {
                    console()->error('Invalid asset output declared for '.$package_name.'.');

                    return 2;
                }
                $source_path = realpath($package_path.'/'.$output);
                $destination_path = base_path($destination);
                if (! is_string($source_path) || ! is_file($source_path)) {
                    console()->error('The asset build did not produce '.$package_name.'/'.$output.'.');

                    return 1;
                }
                if (! is_dir(dirname($destination_path)) && ! mkdir(dirname($destination_path), 0775, true) && ! is_dir(dirname($destination_path))) {
                    console()->error('Unable to create the asset output directory.');

                    return 1;
                }
                if (! copy($source_path, $destination_path)) {
                    console()->error('Unable to publish '.$package_name.'/'.$output.'.');

                    return 1;
                }
                console()->info('Published '.$destination.'.');
            }
        }

        return 0;
    }

    /** @return array<string, string>|null */
    private function selectPackages(string $package, array $arguments): ?array
    {
        $workspace_root = realpath(dirname(base_path()));
        if (! is_string($workspace_root)) {
            console()->error('Unable to resolve the workspace directory.');

            return null;
        }

        $packages = [];
        foreach (InstalledVersions::getInstalledPackages() as $package_name) {
            $install_path = InstalledVersions::getInstallPath($package_name);
            $package_path = is_string($install_path) ? realpath($install_path) : false;
            $workspace_package_path = base_path().'/webkernel/packages/'.substr($package_name, strlen('webkernel/'));
            if (
                str_starts_with($package_name, 'webkernel/')
                && is_file($workspace_package_path.'/composer.json')
            ) {
                $package_path = realpath($workspace_package_path) ?: $workspace_package_path;
            }
            $relative_package_path = is_string($package_path)
                ? str_replace(DIRECTORY_SEPARATOR, '/', substr($package_path, strlen($workspace_root) + 1))
                : '';
            $is_generated_dependency = preg_match('~(?:^|/)internal/dependencies(?:/|$)~', $relative_package_path) === 1;
            if (
                ! is_string($package_path)
                || ! str_starts_with($package_path, $workspace_root.DIRECTORY_SEPARATOR)
                || $is_generated_dependency
                || in_array('vendor', explode('/', $relative_package_path), true)
                || in_array('node_modules', explode('/', $relative_package_path), true)
                || ! is_file($package_path.'/package.json')
            ) {
                continue;
            }

            try {
                $manifest = json_decode(
                    (string) file_get_contents($package_path.'/package.json'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (\JsonException) {
                console()->error('Invalid package.json in '.$package_name.'.');

                return null;
            }

            $scripts = is_array($manifest) ? ($manifest['scripts'] ?? null) : null;
            if (is_array($scripts) && is_string($scripts['build'] ?? null)) {
                $packages[$package_name] = $package_path;
            }
        }

        foreach ($this->declaredAssetBuilds() as $package_name => $declaration) {
            $source = $declaration['source'] ?? null;
            if (! is_string($source) || preg_match('~\A[a-z0-9_.\/-]+\z~i', $source) !== 1) {
                console()->error('Invalid asset build source declared for '.$package_name.'.');

                return null;
            }
            $package_path = realpath(base_path($source));
            if (! is_string($package_path) || ! is_file($package_path.'/package.json')) {
                continue;
            }
            try {
                $manifest = json_decode((string) file_get_contents($package_path.'/package.json'), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                console()->error('Invalid package.json in '.$package_name.'.');

                return null;
            }
            if (is_string($manifest['scripts']['build'] ?? null)) {
                $packages[$package_name] = $package_path;
            }
        }

        $root_package = InstalledVersions::getRootPackage();
        $root_name = $root_package['name'] ?? null;
        $root_path = realpath(base_path());
        if (
            is_string($root_name)
            && is_string($root_path)
            && is_file($root_path.'/package.json')
        ) {
            try {
                $root_manifest = json_decode(
                    (string) file_get_contents($root_path.'/package.json'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (\JsonException) {
                console()->error('Invalid package.json in '.$root_name.'.');

                return null;
            }

            $root_scripts = is_array($root_manifest) ? ($root_manifest['scripts'] ?? null) : null;
            if (is_array($root_scripts) && is_string($root_scripts['build'] ?? null)) {
                $packages[$root_name] = $root_path;
            }
        }
        ksort($packages);

        if ($arguments !== [] || ($package !== 'all' && ! isset($packages[$package]) && is_dir(base_path($package)))) {
            $expanded_paths = [$package, ...$arguments];
            $first_directory = dirname($package);
            $shell_expanded_glob = str_starts_with($package, 'webkernel/')
                && $first_directory !== '.'
                && array_all(
                    $expanded_paths,
                    static fn (string $path): bool => dirname($path) === $first_directory
                        && str_starts_with($path, 'webkernel/')
                        && is_dir(base_path($path)),
                );
            if ($shell_expanded_glob) {
                $package = $first_directory.'/*';
            } elseif ($arguments !== []) {
                console()->error('Pass one package selector at a time.');

                return null;
            }
        }

        $selected_packages = $package === 'all'
            ? $packages
            : array_filter(
                $packages,
                static fn (string $_path, string $package_name): bool => fnmatch($package, $package_name),
                ARRAY_FILTER_USE_BOTH,
            );
        if ($selected_packages === []) {
            console()->error('No asset build package matches ['.$package.']. Available: '.implode(', ', array_keys($packages)));

            return null;
        }

        return $selected_packages;
    }

    /** @return array{binary: string, lockfile: string, install_arguments: list<string>, build_arguments: list<string>, production_prune_arguments: list<string>|null}|null */
    private function packageManager(): ?array
    {
        $manager = Config::get('javascript.manager', 'npm');
        if (! is_string($manager) || preg_match('/\A[a-z][a-z0-9-]*\z/', $manager) !== 1) {
            console()->error('The configured JavaScript package manager is invalid.');

            return null;
        }

        $manager_commands = [
            'npm' => ['lockfile' => 'package-lock.json', 'install_arguments' => ['ci'], 'build_arguments' => ['run', 'build'], 'production_prune_arguments' => ['prune', '--omit=dev', '--ignore-scripts']],
            'pnpm' => ['lockfile' => 'pnpm-lock.yaml', 'install_arguments' => ['install', '--frozen-lockfile'], 'build_arguments' => ['run', 'build'], 'production_prune_arguments' => ['install', '--prod', '--frozen-lockfile', '--ignore-scripts']],
            'yarn' => ['lockfile' => 'yarn.lock', 'install_arguments' => ['install', '--immutable'], 'build_arguments' => ['build'], 'production_prune_arguments' => null],
        ];
        if (! isset($manager_commands[$manager])) {
            console()->error('Unsupported JavaScript package manager ['.$manager.'].');

            return null;
        }

        $binary = self::binaryPath($manager);
        if ($binary === null) {
            console()->error($manager.' was not found on PATH.');

            return null;
        }

        return ['binary' => $binary, ...$manager_commands[$manager]];
    }

    /** Remove development-only packages after a production asset build. */
    public function pruneProductionDependencies(): int
    {
        $packages = $this->selectNodePackages();
        if ($packages === null) {
            return 2;
        }
        $manager = $this->packageManager();
        if ($manager === null) {
            return 127;
        }
        if ($manager['production_prune_arguments'] === null) {
            console()->error('Production pruning is not available for Yarn package-local installs yet; use npm or pnpm for deploy:prod.');

            return 2;
        }
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['TMPDIR'] = tmp_path();
        $environment['NODE_ENV'] = 'production';

        foreach ($packages as $package_name => $package_path) {
            if (! is_dir($package_path.'/node_modules')) {
                continue;
            }
            if (is_link($package_path.'/node_modules')) {
                console()->error('Refusing to change linked node_modules for '.$package_name.'.');

                return 2;
            }
            if (! is_file($package_path.'/'.$manager['lockfile'])) {
                console()->error('Cannot safely prune '.$package_name.': missing '.$manager['lockfile'].'.');

                return 2;
            }
            console()->info('Keeping production Node dependencies for '.$package_name.' and removing development dependencies...');
            $result = self::runProcess(
                [$manager['binary'], ...$manager['production_prune_arguments']],
                $package_path,
                $environment,
            );
            if ($result !== 0) {
                return $result;
            }
        }

        return 0;
    }

    public function supportsProductionPruning(): bool
    {
        return Config::get('javascript.manager', 'npm') !== 'yarn';
    }

    /** @return array<string, string>|null */
    private function selectNodePackages(): ?array
    {
        $workspace_root = realpath(dirname(base_path()));
        if (! is_string($workspace_root)) {
            console()->error('Unable to resolve the workspace directory.');

            return null;
        }

        $packages = [];
        foreach (InstalledVersions::getInstalledPackages() as $package_name) {
            $install_path = InstalledVersions::getInstallPath($package_name);
            $package_path = is_string($install_path) ? realpath($install_path) : false;
            $workspace_package_path = base_path().'/webkernel/packages/'.substr($package_name, strlen('webkernel/'));
            if (
                str_starts_with($package_name, 'webkernel/')
                && is_file($workspace_package_path.'/composer.json')
            ) {
                $package_path = realpath($workspace_package_path) ?: $workspace_package_path;
            }
            $relative_path = is_string($package_path)
                ? str_replace(DIRECTORY_SEPARATOR, '/', substr($package_path, strlen($workspace_root) + 1))
                : '';
            if (
                ! is_string($package_path)
                || ! str_starts_with($package_path, $workspace_root.DIRECTORY_SEPARATOR)
                || preg_match('~(?:^|/)internal/dependencies(?:/|$)~', $relative_path) === 1
                || in_array('vendor', explode('/', $relative_path), true)
                || in_array('node_modules', explode('/', $relative_path), true)
                || ! is_file($package_path.'/package.json')
            ) {
                continue;
            }
            $packages[$package_name] = $package_path;
        }

        foreach ($this->declaredAssetBuilds() as $package_name => $declaration) {
            if (! is_array($declaration) || ! is_string($declaration['source'] ?? null)) {
                continue;
            }
            $package_path = realpath(base_path($declaration['source']));
            if (is_string($package_path) && is_file($package_path.'/package.json')) {
                $packages[$package_name] = $package_path;
            }
        }

        $root = InstalledVersions::getRootPackage();
        $root_name = $root['name'] ?? null;
        $root_path = realpath(base_path());
        if (is_string($root_name) && is_string($root_path) && is_file($root_path.'/package.json')) {
            $packages[$root_name] = $root_path;
        }
        ksort($packages, SORT_STRING);

        return $packages;
    }

    /** @param array<string, string> $packages @param array{binary: string, lockfile: string, install_arguments: list<string>, build_arguments: list<string>, production_prune_arguments: list<string>|null} $manager @param array<string, string> $environment */
    private function installPackages(array $packages, array $manager, array $environment): int
    {
        foreach ($packages as $package_name => $package_path) {
            if (! is_file($package_path.'/'.$manager['lockfile'])) {
                console()->error('Missing dependency lockfile for '.$package_name.'.');

                return 2;
            }

            console()->info('Installing locked dependencies for '.$package_name.'...');
            $result = self::runProcess(
                [$manager['binary'], ...$manager['install_arguments']],
                $package_path,
                $environment,
            );
            if ($result !== 0) {
                return $result;
            }
        }

        return 0;
    }

    /** @param array<string, string> $packages @param array{binary: string, lockfile: string, install_arguments: list<string>, build_arguments: list<string>, production_prune_arguments: list<string>|null} $manager @param array<string, string> $environment */
    private function buildPackages(array $packages, array $manager, array $environment): int
    {
        foreach ($packages as $package_name => $package_path) {
            console()->info('Building '.$package_name.'...');
            $result = self::runProcess(
                [$manager['binary'], ...$manager['build_arguments']],
                $package_path,
                $environment,
            );
            if ($result !== 0) {
                return $result;
            }
        }

        return 0;
    }

    private static function binaryPath(string $binary): ?string
    {
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$binary;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param list<string> $command @param array<string, string> $environment */
    private static function runProcess(array $command, string $working_directory, array $environment): int
    {
        $process = proc_open(
            $command,
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            $working_directory,
            $environment,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            console()->error('Unable to start '.$command[0].'.');

            return 127;
        }

        return proc_close($process);
    }
}
