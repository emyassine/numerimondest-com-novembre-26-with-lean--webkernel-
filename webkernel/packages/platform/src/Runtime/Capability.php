<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

/** Optional facilities that can accelerate the baseline runtime. */
enum Capability: string
{
    case Opcache = 'opcache';
    case Apcu = 'apcu';
    case FinishRequest = 'finish_request';
    case Sodium = 'sodium';
    case ProcOpen = 'proc_open';

    public function isAvailable(): bool
    {
        return match ($this) {
            self::Opcache => function_exists('opcache_get_status') && self::enabled((string) ini_get('opcache.enable')),
            self::Apcu => function_exists('apcu_fetch') && self::enabled((string) ini_get('apc.enabled')),
            self::FinishRequest => function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request'),
            self::Sodium => extension_loaded('sodium'),
            self::ProcOpen => function_exists('proc_open'),
        };
    }

    private static function enabled(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'on', 'yes', 'true'], true);
    }
}
