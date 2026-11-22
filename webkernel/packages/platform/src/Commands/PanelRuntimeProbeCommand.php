<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Commands;

use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\Platform\Runtime\WorkerProbe;

/** Checks whether the current host can keep a PHP worker alive between requests. */
final class PanelRuntimeProbeCommand
{
    /** @param $action 'begin'|'verify' */
    #[ConsoleCommand(name: 'panel:runtime-probe', description: 'Probe optional PHP worker and Unix socket capabilities')]
    public function __invoke(string $action = 'verify'): int
    {
        if (! in_array($action, ['begin', 'verify'], true)) {
            console()->error('Use "begin" to start the child-process probe or "verify" on a later request.');
            return 2;
        }
        $probe = new WorkerProbe(base_path('storage/private/panel-runtime'));
        $result = $action === 'begin' ? $probe->begin() : $probe->verify();
        if (($result['php_worker'] ?? null) === null) {
            console()->info('Probe started or still pending. Run panel:runtime-probe verify after the parent request has ended.');
            return 0;
        }
        if (($result['php_worker'] ?? false) !== true) {
            console()->warning('The PHP worker tier is unavailable: '.(string) ($result['reason'] ?? 'probe_failed').'.');
            return 1;
        }
        console()->success('The child survived and a Unix socket round trip completed in '.number_format((float) $result['socket_round_trip_ms'], 3).' ms. Benchmark the real panel before enabling the worker tier.');
        return 0;
    }
}
