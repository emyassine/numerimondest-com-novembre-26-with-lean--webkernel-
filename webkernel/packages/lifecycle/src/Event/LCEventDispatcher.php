<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Event;

use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\Declaration\LCClassReferenceResolver;

/**
 * Dispatches extra.webkernel.lifecycle.events.{event} callables
 * declared across installed packages.
 *
 * Callable formats:
 *   "Vendor\\ClassName"          → new ClassName(); __invoke($event)
 *   "Vendor\\ClassName::method"  → static or instance call
 *
 * Order: webkernel/codebase first, then alphabetical.
 */
final class LCEventDispatcher
{
    /**
     * Validates and dispatches callbacks declared for one Composer event.
     *
     * @param $event_name string Composer event name.
     * @param $event object Event object passed to each callback.
     * @param $packages list<array{name: string, extra: array<string, mixed>, psr4_prefixes: list<string>}> Root and installed declarations.
     * @param $started_at int Event start time in nanoseconds.
     * @return void
     * @throws \Throwable If a callback declaration is invalid or a callback fails.
     */
    public function dispatch(
        string $event_name,
        object $event,
        array $packages,
        int $started_at,
    ): void
    {
        $hook = LCEventTarget::tryFrom($event_name);

        if ($hook === null) {
            return;
        }

        $key  = $hook->extraKey();
        $jobs = [];

        $class_reference_resolver = new LCClassReferenceResolver();
        foreach ($packages as $package) {
            $target = $package['extra']['lifecycle']['events'][$key] ?? null;

            if ($target === null || $target === '') {
                continue;
            }

            $targets = is_string($target) ? [$target] : $target;
            if (! is_array($targets) || ! array_is_list($targets) || $targets === []) {
                throw new \RuntimeException(sprintf(
                    '[webkernel/lifecycle] Event listener declaration for "%s" in package "%s" must be a callback or a non-empty callback list.',
                    $key,
                    $package['name'],
                ));
            }

            foreach ($targets as $event_target) {
                if (! is_string($event_target)) {
                    throw new \RuntimeException(sprintf(
                        '[webkernel/lifecycle] Event listener declaration for "%s" in package "%s" contains a non-string callback.',
                        $key,
                        $package['name'],
                    ));
                }
                $jobs[] = [
                    'name' => $package['name'],
                    'target' => $class_reference_resolver->resolve(
                        $event_target,
                        $package['psr4_prefixes'],
                        $package['name'],
                    ),
                ];
            }
        }

        $check_count = 0;
        $action_count = 0;
        foreach ($packages as $package) {
            $check_count += count($package['extra']['lifecycle']['checks'] ?? []);
            $action_count += count($package['extra']['lifecycle']['actions'] ?? []);
        }
        if ($event_name !== 'post-autoload-dump') {
            $action_count = 0;
        }
        if (! in_array($event_name, ['pre-install-cmd', 'pre-update-cmd', 'post-autoload-dump'], strict: true)) {
            $check_count = 0;
        }

        usort($jobs, static function (array $a, array $b): int {
            $p = static fn (string $n): int => $n === 'webkernel/codebase' ? 0 : 1;
            return [$p($a['name']), $a['name']] <=> [$p($b['name']), $b['name']];
        });

        foreach ($jobs as $job) {
            $this->validateTarget($job['target']);
        }

        if ($jobs === []) {
            return;
        }

        StandardComposerIO::tree($event_name, function () use ($jobs, $event, $started_at): void {
            try {
                foreach ($jobs as $job) {
                    $listener_started_at = hrtime(true);

                    StandardComposerIO::branch(self::displayTarget($job['target'], $job['name']), function () use ($job, $event, $listener_started_at): void {
                        StandardComposerIO::blankLine(continue_current_branch: true);

                        try {
                            $this->invoke($job['target'], $event);
                            StandardComposerIO::result(
                                'DONE',
                                'Listener completed',
                                StandardComposerIO::duration(hrtime(true) - $listener_started_at),
                            );
                            StandardComposerIO::blankLine();
                        } catch (\Throwable $exception) {
                            StandardComposerIO::result(
                                'FAIL',
                                sprintf('%s: %s', $exception::class, $exception->getMessage()),
                                StandardComposerIO::duration(hrtime(true) - $listener_started_at),
                            );
                            StandardComposerIO::blankLine();
                            throw $exception;
                        }
                    });
                }
            } catch (\Throwable $exception) {
                StandardComposerIO::result(
                    'FAIL',
                    'Event stopped',
                    StandardComposerIO::duration(hrtime(true) - $started_at),
                );
                throw $exception;
            }

            StandardComposerIO::result(
                'DONE',
                sprintf('Event complete · %d listener(s)', count($jobs)),
                StandardComposerIO::duration(hrtime(true) - $started_at),
            );
        }, static function () use ($event_name, $jobs, $check_count, $action_count, $started_at): string {
            $summary = sprintf(
                '<fg=gray>completed %s in %s · %s</>',
                $event_name,
                StandardComposerIO::duration(hrtime(true) - $started_at),
                self::countLabel(count($jobs), 'callback'),
            );
            if ($check_count > 0 || $action_count > 0) {
                $summary .= sprintf(
                    ' · %s · %s',
                    self::countLabel($check_count, 'check'),
                    self::countLabel($action_count, 'action'),
                );
            }

            return $summary;
        });
    }

    /**
     * Invokes a listener class or ClassName::method callback.
     *
     * @param $target string Listener class or class and method.
     * @param $event object Composer event passed to the listener.
     * @return void
     * @throws \Throwable If the callback cannot be resolved or invocation fails.
     */
    private function invoke(string $target, object $event): void
    {
        $class  = $target;
        $method = '__invoke';

        if (str_contains($target, '::')) {
            [$class, $method] = explode('::', $target, 2);
        }

        if (! class_exists($class)) {
            throw new \RuntimeException('[webkernel/lifecycle] Listener class not found: ' . $class);
        }

        if (! method_exists($class, $method)) {
            throw new \RuntimeException('[webkernel/lifecycle] Hook method not found: ' . $target);
        }

        $ref  = new \ReflectionMethod($class, $method);
        if (! $ref->isPublic()) {
            throw new \RuntimeException('[webkernel/lifecycle] Hook method must be public: ' . $target);
        }

        if (! $ref->isStatic() && (new \ReflectionClass($class))->isAbstract()) {
            throw new \RuntimeException('[webkernel/lifecycle] Listener class must be instantiable: ' . $class);
        }

        $args = $ref->getNumberOfParameters() > 0 ? [$event] : [];

        $ref->isStatic()
            ? $ref->invokeArgs(null, $args)
            : $ref->invokeArgs(new $class(), $args);
    }

    /**
     * Validates the class, method, visibility, and invocation requirements of a callback.
     * @param $target string Listener class or class and method.
     * @return void
     * @throws \Throwable If the callback class or method cannot be validated.
     */
    private function validateTarget(string $target): void
    {
        $class = $target;
        $method = '__invoke';
        if (str_contains($target, '::')) {
            [$class, $method] = explode('::', $target, 2);
        }

        if (! class_exists($class)) {
            throw new \RuntimeException('[webkernel/lifecycle] Listener class not found: ' . $class);
        }

        if (! method_exists($class, $method)) {
            throw new \RuntimeException('[webkernel/lifecycle] Hook method not found: ' . $target);
        }

        $method_reflection = new \ReflectionMethod($class, $method);
        if (! $method_reflection->isPublic()) {
            throw new \RuntimeException('[webkernel/lifecycle] Hook method must be public: ' . $target);
        }

        if ($method_reflection->getNumberOfRequiredParameters() > 1) {
            throw new \RuntimeException('[webkernel/lifecycle] Hook method may require at most one event argument: ' . $target);
        }

        if (! $method_reflection->isStatic()) {
            $class_reflection = new \ReflectionClass($class);
            if (! $class_reflection->isInstantiable()) {
                throw new \RuntimeException('[webkernel/lifecycle] Listener class must be instantiable: ' . $class);
            }

            $constructor = $class_reflection->getConstructor();
            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                throw new \RuntimeException('[webkernel/lifecycle] Listener constructor must not require arguments: ' . $class);
            }
        }
    }

    /**
     * Formats a listener target with its owning package for display.
     * @param $target string Listener class or class and method.
     * @param $package_name string Declaring Composer package.
     * @return string Display label.
     */
    private static function displayTarget(string $target, string $package_name): string
    {
        [$class, $method] = array_pad(explode('::', $target, 2), 2, null);
        $separator = strrpos($class, '\\');
        $short_class = $separator === false ? $class : substr($class, $separator + 1);

        $target_name = $method === null ? $short_class : $short_class . '::' . $method;

        return sprintf('%s <fg=gray>(%s)</>', $target_name, $package_name);
    }

    /**
     * Formats a count with the singular or plural item name.
     * @param $count int Number of items.
     * @param $item string Singular item label.
     * @return string Formatted count.
     */
    private static function countLabel(int $count, string $item): string
    {
        return sprintf('<fg=gray>%d %s%s</>', $count, $item, $count === 1 ? '' : 's');
    }
}
