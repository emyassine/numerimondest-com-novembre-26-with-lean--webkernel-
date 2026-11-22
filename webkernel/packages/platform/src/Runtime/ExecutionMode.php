<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

/** Selects the execution path for a compiled panel request. */
enum ExecutionMode: string
{
    case InProcess = 'in_process';
    case PhpWorker = 'php_worker';
    case NativeWorker = 'native_worker';

    public function requiresChildProcess(): bool
    {
        return $this !== self::InProcess;
    }
}
