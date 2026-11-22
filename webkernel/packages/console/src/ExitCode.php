<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

enum ExitCode: int
{
    case SUCCESS = 0;
    case ERROR = 1;
    case INVALID = 2;
    case HUNG_UP = 129;
    case INTERRUPTED = 130;
    case QUIT = 131;
    case TERMINATED = 143;
}
