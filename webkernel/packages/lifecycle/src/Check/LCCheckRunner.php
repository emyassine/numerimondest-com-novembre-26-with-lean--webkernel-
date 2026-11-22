<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Check;

use Composer\Script\Event;
use Webkernel\Lifecycle\Declaration\LCDeclarationReader;
use Webkernel\Lifecycle\StandardComposerIO;

/**
 * Evaluates environment checks declared in extra.webkernel.lifecycle.checks[].
 *
 * Checks are PHP_VERSION_ID comparisons against integers. True means failed.
 *
 * Severity:
 *   info    → blue notice, continues.
 *   warning → yellow warning, continues.
 *   danger  → red error, throws RuntimeException, blocks Composer.
 */
final class LCCheckRunner
{
    /**
     * Evaluates declared environment checks and blocks on danger-level failures.
     * @param $event Event Composer event whose package declarations are checked.
     * @throws \Throwable If a blocking check or declaration fails.
     */
    public function run(Event $event): void
    {
        $started_at = hrtime(true);
        $packages = $this->collectPackages($event);
        $declared = array_sum(array_map(
            static fn (array $package): int => count($package['checks']),
            $packages,
        ));
        $passed = 0;
        $warnings = 0;
        $info = 0;
        $failed = 0;
        $skipped = 0;

        if ($declared === 0) {
            StandardComposerIO::result('SKIP', 'No checks declared', StandardComposerIO::duration(hrtime(true) - $started_at));
            return;
        }

        try {
            foreach ($packages as ['name' => $pkg_name, 'checks' => $checks]) {
                foreach ($checks as $spec) {
                    $check_started_at = hrtime(true);

                    if (! is_array($spec)) {
                        $skipped++;
                        StandardComposerIO::result(
                            'SKIP',
                            sprintf('Invalid declaration (package: %s)', $pkg_name),
                            StandardComposerIO::duration(hrtime(true) - $check_started_at),
                        );
                        continue;
                    }

                    $expression = $spec['check'] ?? null;
                    if (! is_string($expression) || $expression === '') {
                        $skipped++;
                        StandardComposerIO::result(
                            'SKIP',
                            sprintf('Check has no expression (package: %s)', $pkg_name),
                            StandardComposerIO::duration(hrtime(true) - $check_started_at),
                        );
                        continue;
                    }

                    $result = $this->evaluate($spec);
                    if ($result['status'] === 'unsupported') {
                        $skipped++;
                        StandardComposerIO::result(
                            'SKIP',
                            sprintf('Unsupported check expression "%s" (package: %s)', $expression, $pkg_name),
                            StandardComposerIO::duration(hrtime(true) - $check_started_at),
                        );
                        continue;
                    }

                    StandardComposerIO::branch(
                        sprintf('<fg=gray>[CHECK]</> %s <fg=gray>(%s)</>', $expression, $pkg_name),
                        function () use ($result, $expression, $pkg_name, $check_started_at, &$passed, &$warnings, &$info, &$failed): void {
                            $duration = StandardComposerIO::duration(hrtime(true) - $check_started_at);

                            if ($result['status'] === 'pass') {
                                $passed++;
                                StandardComposerIO::result('PASS', 'Environment satisfies the requirement', $duration);
                                return;
                            }

                            if ($result['status'] === 'warning') {
                                $warnings++;
                                $label = 'WARN';
                                $message = $result['message'] ?? 'Requirement not met; execution continues';
                            } elseif ($result['status'] === 'info') {
                                $info++;
                                $label = 'INFO';
                                $message = $result['message'] ?? 'Requirement not met; execution continues';
                            } else {
                                $failed++;
                                $label = 'BLOCKED';
                                $message = $result['message'] ?? 'Requirement not met; Composer operation stopped';
                            }

                            if ($result['fix'] !== null) {
                                $message .= sprintf(' · Suggested fix: %s', $result['fix']);
                            }

                            StandardComposerIO::result($label, $message, $duration);

                            if ($result['status'] === 'danger') {
                                throw new \RuntimeException(sprintf(
                                    '[webkernel/lifecycle] Blocking check failed in package "%s".',
                                    $pkg_name,
                                ));
                            }
                        },
                    );
                }
            }
        } catch (\Throwable $exception) {
            $this->writeSummary($declared, $passed, $warnings, $info, $failed, $skipped, $started_at);
            throw $exception;
        }

        $this->writeSummary($declared, $passed, $warnings, $info, $failed, $skipped, $started_at);
    }

    /**
     * Writes totals for checks evaluated during the current event.
     * @param $declared int Total declared checks.
     * @param $passed int Checks that passed.
     * @param $warnings int Checks that produced warnings.
     * @param $info int Informational check results.
     * @param $failed int Checks that blocked the event.
     * @param $skipped int Checks that could not be evaluated.
     * @param $started_at int Start time in nanoseconds.
     */
    private function writeSummary(
        int $declared,
        int $passed,
        int $warnings,
        int $info,
        int $failed,
        int $skipped,
        int $started_at,
    ): void {
        StandardComposerIO::line(sprintf(
            '<fg=gray>%d declared</> · <fg=green>%d passed</> · <fg=yellow>%d warnings</> · '
            . '<fg=cyan>%d info</> · <fg=red>%d blocked</> · <fg=gray>%d skipped</> · <fg=gray>%s</>',
            $declared,
            $passed,
            $warnings,
            $info,
            $failed,
            $skipped,
            StandardComposerIO::duration(hrtime(true) - $started_at),
        ));
        StandardComposerIO::blankLine();
    }

    /**
     * Evaluates a check specification and returns its status and user message.
     * @param $spec array<string, mixed> Check declaration.
     * @return array{status: 'pass'|'warning'|'info'|'danger'|'unsupported', message: ?string, fix: ?string}
     * @throws \Throwable If the check declaration cannot be evaluated.
     */
    private function evaluate(array $spec): array
    {
        $expression = $spec['check']   ?? null;
        $on_fail    = $spec['on-fail'] ?? [];
        $fix        = $spec['fix']     ?? null;

        $failed = $this->evaluateCheck((string) $expression);

        if ($failed === null) {
            return ['status' => 'unsupported', 'message' => null, 'fix' => null];
        }

        if (! $failed) {
            return ['status' => 'pass', 'message' => null, 'fix' => null];
        }

        $type = is_array($on_fail) ? ($on_fail['type'] ?? 'warning') : 'warning';
        $message = is_array($on_fail) ? ($on_fail['message'] ?? 'An environment check failed.') : 'An environment check failed.';
        $type = is_string($type) ? $type : 'warning';
        $message = is_string($message) ? $message : 'An environment check failed.';

        $status = match ($type) {
            'danger' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
        $fix = is_string($fix) && $fix !== '' ? $fix : null;

        return ['status' => $status, 'message' => $message, 'fix' => $fix];
    }

    /**
     * Evaluates a supported PHP_VERSION_ID comparison without evaluating PHP code.
     * @param $expression string Comparison expression.
     * @return bool|null Whether the comparison failed, or null if unsupported.
     */
    private function evaluateCheck(string $expression): ?bool
    {
        if (! preg_match('/^\s*PHP_VERSION_ID\s*(===|==|!==|!=|<=|>=|<|>)\s*(\d+)\s*$/D', $expression, $matches)) {
            return null;
        }

        $version_id = (int) $matches[2];

        return match ($matches[1]) {
            '===' , '==' => PHP_VERSION_ID === $version_id,
            '!==' , '!=' => PHP_VERSION_ID !== $version_id,
            '<' => PHP_VERSION_ID < $version_id,
            '<=' => PHP_VERSION_ID <= $version_id,
            '>' => PHP_VERSION_ID > $version_id,
            '>=' => PHP_VERSION_ID >= $version_id,
            default => null,
        };
    }

    /**
     * Collects check declarations from the root and installed packages.
     * @param $event Event Composer event whose package declarations are read.
     * @return list<array{name: string, checks: list<mixed>}>
     * @throws \RuntimeException If a package declaration is invalid.
     */
    private function collectPackages(Event $event): array
    {
        $out = [];

        foreach ((new LCDeclarationReader())->read($event->getComposer()) as $package) {
            $name = $package['name'];
            $checks = $package['extra']['lifecycle']['checks'] ?? [];

            if (! is_array($checks) || $checks === []) {
                continue;
            }

            $out[] = ['name' => $name, 'checks' => array_values($checks)];
        }

        return $out;
    }

}
