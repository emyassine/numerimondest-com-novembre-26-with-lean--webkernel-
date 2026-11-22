<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Workers;

use RuntimeException;
use Webkernel\Lifecycle\StandardLCEvent;
use Webkernel\Lifecycle\Action\LCActionRunner;
use Webkernel\Lifecycle\Check\LCCheckRunner;
use Webkernel\Lifecycle\StandardComposerIO;

/** Runs the checks and declared actions after Composer rebuilds its autoloader. */
final class EventPostAutoloadDump extends StandardLCEvent
{
    /** @throws \Throwable If a check or action phase fails. */
    protected function run(): void
    {
        $event = $this->composerEvent();

        StandardComposerIO::branch('Environment checks', static function () use ($event): void {
            (new LCCheckRunner())->run($event);
        }, 'cyan');

        $failed_actions = StandardComposerIO::branch('Declared actions', static function () use ($event): int {
            return (new LCActionRunner())->run($event);
        }, 'cyan');

        if ($failed_actions > 0) {
            throw new RuntimeException(sprintf(
                '%d lifecycle action(s) failed; see the action results above.',
                $failed_actions,
            ));
        }
    }
}
