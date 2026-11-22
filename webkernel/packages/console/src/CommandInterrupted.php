<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

use RuntimeException;

/** Indicates that a console command was stopped by an operating-system signal. */
final class CommandInterrupted extends RuntimeException
{
    public function __construct(public readonly int $signal)
    {
        parent::__construct('Command interrupted by signal '.$signal.'.');
    }
}
