<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

/** Installs interrupt handling for the lifetime of one dispatched command. */
final class CommandSignals
{
    /** @template T @param callable(): T $command @return T */
    public static function run(callable $command): mixed
    {
        if (! function_exists('pcntl_signal') || ! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal_get_handler')) {
            return $command();
        }

        $signals = [SIGINT];
        if (defined('SIGTERM')) {
            $signals[] = SIGTERM;
        }
        $previous_async = pcntl_async_signals();
        $previous_handlers = [];
        foreach ($signals as $signal) {
            $previous_handlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, static function (int $received_signal): never {
                throw new CommandInterrupted($received_signal);
            });
        }
        pcntl_async_signals(true);

        try {
            return $command();
        } finally {
            foreach ($previous_handlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
            pcntl_async_signals($previous_async);
        }
    }
}
