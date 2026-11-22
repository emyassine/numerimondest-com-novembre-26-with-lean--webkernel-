<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Action;

use Composer\Script\Event;
use RuntimeException;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\Contracts\LifecycleActionContract;
use Webkernel\Lifecycle\Declaration\LCClassReferenceResolver;
use Webkernel\Lifecycle\Declaration\LCDeclarationReader;

/**
 * Resolves and runs every action declared in extra.webkernel.lifecycle.actions[]
 * across all installed packages (root + installed).
 *
 * Execution order:
 *   1. Root package actions first.
 *   2. webkernel/codebase actions second (platform core).
 *   3. All other packages in alphabetical order.
 */
final class LCActionRunner
{
    /**
     * Runs package actions and returns the number that failed.
     * @param $event Event Composer event that triggered the action phase.
     * @return int Number of actions that failed.
     * @throws \ReflectionException If a declared class cannot be reflected.
     * @throws RuntimeException If an action declaration is invalid.
     * @throws \Throwable If rendering an action result fails.
     */
    public function run(Event $event): int
    {
        $started_at = hrtime(true);
        $packages = $this->collectPackages($event);
        $declared = array_sum(array_map(
            static fn (array $package): int => count($package['actions']),
            $packages,
        ));

        if ($declared === 0) {
            StandardComposerIO::result('SKIP', 'No actions declared', StandardComposerIO::duration(hrtime(true) - $started_at));
            return 0;
        }

        $this->validateDeclarations($packages);

        $completed = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($packages as ['name' => $pkg_name, 'actions' => $action_fqcns]) {
            foreach ($action_fqcns as $fqcn) {
                $action_started_at = hrtime(true);

                StandardComposerIO::branch(
                    sprintf('<fg=gray>[RUN]</> %s <fg=gray>(%s)</>', self::displayName($fqcn), $pkg_name),
                    function () use ($fqcn, $event, $action_started_at, &$completed, &$failed): void {
                        try {
                            $action = new $fqcn();
                            $action_name = $action->name();
                            $action->handle($event);
                            $completed++;
                            StandardComposerIO::result('DONE', $action_name, StandardComposerIO::duration(hrtime(true) - $action_started_at));
                        } catch (\Throwable $exception) {
                            $failed++;
                            StandardComposerIO::result(
                                'FAIL',
                                sprintf('%s: %s at %s:%d', $exception::class, $exception->getMessage(), $exception->getFile(), $exception->getLine()),
                                StandardComposerIO::duration(hrtime(true) - $action_started_at),
                            );
                        }
                    },
                );
            }
        }

        StandardComposerIO::line(sprintf(
            '<fg=gray>%d declared</> · <fg=green>%d completed</> · <fg=gray>%d skipped</> · <fg=red>%d failed</> · <fg=gray>%s</>',
            $declared, $completed, $skipped, $failed, StandardComposerIO::duration(hrtime(true) - $started_at),
        ));

        return $failed;
    }

    /** @param $class string Fully qualified action class name. @return string Short display name. */
    private static function displayName(string $class): string
    {
        $separator = strrpos($class, '\\');
        $short_class = $separator === false ? $class : substr($class, $separator + 1);

        return preg_replace('/Action$/', '', $short_class) ?: $short_class;
    }

    /**
     * Validates every declared action before any action is executed.
     * @param $packages list<array{name: string, actions: list<string>}>
     * @throws RuntimeException If an action declaration is invalid.
     */
    private function validateDeclarations(array $packages): void
    {
        foreach ($packages as ['name' => $package_name, 'actions' => $action_fqcns]) {
            foreach ($action_fqcns as $fqcn) {
                if (! is_string($fqcn) || $fqcn === '' || ! class_exists($fqcn)) {
                    throw new RuntimeException(sprintf(
                        '[webkernel/lifecycle] Invalid action declaration in package "%s": class "%s" was not found.',
                        $package_name,
                        is_string($fqcn) ? $fqcn : get_debug_type($fqcn),
                    ));
                }

                $reflection = new \ReflectionClass($fqcn);
                if (! $reflection->isInstantiable() || ! $reflection->implementsInterface(LifecycleActionContract::class)) {
                    throw new RuntimeException(sprintf(
                        '[webkernel/lifecycle] Invalid action declaration in package "%s": "%s" must be an instantiable LifecycleActionContract.',
                        $package_name,
                        $fqcn,
                    ));
                }

                $constructor = $reflection->getConstructor();
                if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                    throw new RuntimeException(sprintf(
                        '[webkernel/lifecycle] Invalid action declaration in package "%s": "%s" constructor must not require arguments.',
                        $package_name,
                        $fqcn,
                    ));
                }
            }
        }
    }

    /**
     * Collects action declarations and orders packages for execution.
     * @param $event Event Composer event whose package declarations are read.
     *
     * @return list<array{name: string, actions: list<string>}> Ordered package action declarations.
     * @throws RuntimeException If a package declaration is invalid.
     */
    private function collectPackages(Event $event): array
    {
        $out = [];
        $class_reference_resolver = new LCClassReferenceResolver();

        foreach ((new LCDeclarationReader())->read($event->getComposer()) as $package) {
            $name = $package['name'];
            $actions = $package['extra']['lifecycle']['actions'] ?? [];

            if (! is_array($actions) || $actions === []) {
                continue;
            }

            $out[] = [
                'name' => $name,
                'actions' => array_map(
                    static fn (string $action): string => $class_reference_resolver->resolve(
                        $action,
                        $package['psr4_prefixes'],
                        $name,
                    ),
                    array_values($actions),
                ),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            $priority = static fn (string $n): int => match ($n) {
                ''                   => -1,
                'webkernel/codebase' => 0,
                default              => 1,
            };
            return [$priority($a['name']), $a['name']] <=> [$priority($b['name']), $b['name']];
        });

        return $out;
    }
}
