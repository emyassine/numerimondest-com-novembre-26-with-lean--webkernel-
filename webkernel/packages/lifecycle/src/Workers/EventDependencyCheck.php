<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Workers;

use Webkernel\Lifecycle\StandardLCEvent;
use Webkernel\Lifecycle\Check\LCCheckRunner;
use Webkernel\Lifecycle\StandardComposerIO;

/** Runs declared environment checks before Composer installs or updates dependencies. */
final class EventDependencyCheck extends StandardLCEvent
{
    /** @throws \Throwable If a blocking check or declaration fails. */
    protected function run(): void
    {
        $event = $this->composerEvent();

        StandardComposerIO::branch('Environment checks', static function () use ($event): void {
            (new LCCheckRunner())->run($event);
        }, 'cyan');
    }
}
