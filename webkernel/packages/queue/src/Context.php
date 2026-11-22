<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue;

final class Context
{
    private static array $hidden = [];

    public static function addHidden(array|string $key, mixed $value = null): void
    {
        self::$hidden = array_replace(self::$hidden, is_array($key) ? $key : [$key => $value]);
    }

    public static function getHidden(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? self::$hidden : (self::$hidden[$key] ?? $default);
    }

    public static function forgetHidden(array|string $keys): void
    {
        foreach ((array) $keys as $key) { unset(self::$hidden[$key]); }
    }

    public static function flush(): void
    {
        self::$hidden = [];
    }
}
