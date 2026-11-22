<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Contracts;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface ExceptionHandler
{
    public function report(Throwable $exception): void;

    public function shouldReport(Throwable $exception): bool;

    public function render(Request $request, Throwable $exception): Response;

    public function renderForConsole(OutputInterface $output, Throwable $exception): void;
}
