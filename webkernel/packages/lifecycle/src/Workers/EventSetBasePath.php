<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Workers;

use Webkernel\Lifecycle\Path\LCBasePathInjector;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\StandardLCEvent;

/** Refreshes and reports the Composer project base path after autoload generation. */
final class EventSetBasePath extends StandardLCEvent
{
    protected function run(): void
    {
        $previous_base_path = $GLOBALS['BASE_PATH'] ?? null;
        LCBasePathInjector::inject($this->composerEvent()->getComposer());

        if ($previous_base_path === $GLOBALS['BASE_PATH']) {
            return;
        }

        StandardComposerIO::result('DONE',
            sprintf('Project base path updated: %s', $GLOBALS['BASE_PATH']),
        );
    }
}
