<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands;

use RuntimeException;
use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;
use Webkernel\ServiceProvider\Deployment\ArtifactPublisher;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;

/** Prepares package-owned runtime artifacts and atomically activates their generation. */
final class DeployCommand
{
    #[ConsoleCommand(name: 'deploy', description: 'Prepare and activate the application runtime artifacts')]
    public function deploy(): int
    {
        return $this->test();
    }

    #[ConsoleCommand(name: 'deploy:test', description: 'Prepare runtime artifacts and publish assets without changing Node dependencies')]
    public function test(): int
    {
        return $this->withDeploymentLock(fn (): int => $this->prepareArtifacts());
    }

    #[ConsoleCommand(name: 'deploy:prod', description: 'Build assets, keep production Node dependencies, then prepare runtime artifacts')]
    public function production(): int
    {
        return $this->withDeploymentLock(function (): int {
            $assets = app(AssetsCommand::class);
            if (! $assets->supportsProductionPruning()) {
                console()->error('Production pruning is not available for Yarn package-local installs yet; use npm or pnpm for deploy:prod.');

                return 2;
            }
            $asset_result = $assets->sync();
            if ($asset_result !== 0) {
                return $asset_result;
            }

            return $this->prepareArtifacts(production: true);
        });
    }

    private function prepareArtifacts(bool $production = false): int
    {
        $root_path = base_path();
        $deploy_root = $root_path.'/runtime/cache/deploy';
        $generation = bin2hex(random_bytes(16));
        $generation_path = $deploy_root.'/'.$generation;
        if (! mkdir($generation_path, 0775, true)) {
            console()->error('Unable to create a deployment generation.');

            return 1;
        }

        $published = false;
        try {
            $this->refreshDeclarations($root_path);
            $manifest_path = $root_path.'/runtime/cache/declarations/webkernel_build_artifacts.php';
            if (! is_file($manifest_path)) {
                throw new RuntimeException('The Composer declaration compiler did not publish the build artifact manifest.');
            }
            $builders = require $manifest_path;
            if (! is_array($builders) || ! array_is_list($builders)) {
                throw new RuntimeException('The build artifact manifest is invalid.');
            }

            DeploymentLock::beginBuild();
            $context = new ArtifactBuildContext($root_path, $generation_path);
            $ordered_builders = $this->orderBuilders($builders);
            $artifacts = [];
            foreach ($ordered_builders as $builder) {
                $key = $builder->artifactKey();
                $builder->build($context);
                $artifact_path = $generation_path.'/'.$key;
                if (! is_dir($artifact_path)) {
                    throw new RuntimeException(sprintf('Builder "%s" did not publish its artifact directory.', $key));
                }
                $artifacts[$key] = 'runtime/cache/deploy/'.$generation.'/'.$key;
                console()->info('Prepared '.$key.'.');
            }

            if ($production) {
                $prune_result = app(AssetsCommand::class)->pruneProductionDependencies();
                if ($prune_result !== 0) {
                    throw new RuntimeException(sprintf('Production Node dependency pruning failed with exit code %d.', $prune_result));
                }
            }

            foreach ($ordered_builders as $builder) {
                if ($builder instanceof ArtifactPublisher) {
                    $builder->publish($context);
                }
            }

            if ($artifacts === []) {
                throw new RuntimeException('No build artifacts are declared by installed packages.');
            }
            DeploymentLock::publish($root_path, $generation, $artifacts);
            $published = true;
            console()->success('Deployment artifacts are active. Remove platform-cache.lock to return to dynamic mode.');

            return 0;
        } catch (\Throwable $exception) {
            if (! $published) {
                $this->removeGeneration($generation_path);
            }
            console()->error($exception->getMessage());

            return 1;
        } finally {
            DeploymentLock::endBuild();
        }
    }

    /** @param callable(): int $operation */
    private function withDeploymentLock(callable $operation): int
    {
        $deploy_root = base_path('runtime/cache/deploy');
        if (! is_dir($deploy_root) && ! mkdir($deploy_root, 0775, true) && ! is_dir($deploy_root)) {
            console()->error('Unable to create the deployment artifact directory.');

            return 1;
        }
        $lock_handle = fopen($deploy_root.'/deploy.lock', 'c');
        if (! is_resource($lock_handle) || ! flock($lock_handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock_handle)) {
                fclose($lock_handle);
            }
            console()->error('Another deployment build is already running.');

            return 1;
        }

        try {
            return $operation();
        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
        }
    }

    private function removeGeneration(string $path): void
    {
        if (! is_dir($path) || is_link($path)) {
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $entry) {
            $entry_path = $entry->getPathname();
            if ($entry->isDir() && ! $entry->isLink()) {
                $this->removeGeneration($entry_path);
                continue;
            }
            @unlink($entry_path);
        }
        @rmdir($path);
    }

    private function refreshDeclarations(string $root_path): void
    {
        $composer = $this->binaryPath('composer');
        if ($composer === null) {
            throw new RuntimeException('Composer was not found on PATH.');
        }
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['TMPDIR'] = tmp_path();
        $process = proc_open(
            [$composer, 'dump-autoload', '--no-interaction', '--no-ansi'],
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            $root_path,
            $environment,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start Composer declaration compilation.');
        }
        $exit_code = proc_close($process);
        if ($exit_code !== 0) {
            throw new RuntimeException(sprintf('Composer declaration compilation failed with exit code %d.', $exit_code));
        }
    }

    private function binaryPath(string $binary): ?string
    {
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$binary;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param list<mixed> $builder_classes @return list<ArtifactBuilder> */
    private function orderBuilders(array $builder_classes): array
    {
        $pending = [];
        foreach ($builder_classes as $builder_class) {
            if (! is_string($builder_class) || ! class_exists($builder_class)) {
                throw new RuntimeException('The build artifact manifest contains an unavailable builder.');
            }
            $builder = app($builder_class);
            if (! $builder instanceof ArtifactBuilder) {
                throw new RuntimeException(sprintf('%s must implement %s.', $builder_class, ArtifactBuilder::class));
            }
            $key = $builder->artifactKey();
            if (preg_match('/\A[a-z][a-z0-9_-]*\z/', $key) !== 1 || isset($pending[$key])) {
                throw new RuntimeException(sprintf('Build artifact key is invalid or duplicated: %s', $key));
            }
            $pending[$key] = $builder;
        }

        $ordered = [];
        $resolved = [];
        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $key => $builder) {
                foreach ($builder->dependencies() as $dependency) {
                    if (! isset($pending[$dependency]) && ! isset($resolved[$dependency])) {
                        throw new RuntimeException(sprintf('Artifact "%s" requires undeclared artifact "%s".', $key, $dependency));
                    }
                }
                if (array_diff($builder->dependencies(), array_keys($resolved)) !== []) {
                    continue;
                }
                $ordered[] = $builder;
                $resolved[$key] = true;
                unset($pending[$key]);
                $progress = true;
            }
            if (! $progress) {
                throw new RuntimeException('Build artifact dependencies contain a cycle.');
            }
        }

        return $ordered;
    }
}
