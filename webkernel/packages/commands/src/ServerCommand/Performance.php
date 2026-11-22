<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\ServerCommand;

final class Performance
{
    public const JIT_MODE = 'tracing';
    public const JIT_BUFFER = '64M';

    public static function wants_jit(): bool
    {
        return (bool) config('performance.jit', false);
    }

    /** @return list<string> */
    public static function jit_engine_args(): array
    {
        return [
            ...self::opcache_engine_args(),
            '-d', 'opcache.jit='.self::JIT_MODE,
            '-d', 'opcache.jit_buffer_size='.self::JIT_BUFFER,
        ];
    }

    /** @return list<string> */
    public static function jit_disable_args(): array
    {
        return [...self::opcache_engine_args(), '-d', 'opcache.jit=disable'];
    }

    /** @return list<string> */
    private static function opcache_engine_args(): array
    {
        $arguments = ['-d', 'opcache.enable=1', '-d', 'opcache.enable_cli=1'];

        if (! extension_loaded('Zend OPcache')) {
            array_unshift($arguments, '-d', 'zend_extension=opcache');
        }

        return $arguments;
    }
}
