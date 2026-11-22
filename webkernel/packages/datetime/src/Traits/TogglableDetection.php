<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\DateTime\Traits;

trait TogglableDetection
{
    private static bool $detectionEnabled = true;

    public static function isDetectionEnabled(): bool
    {
        return self::$detectionEnabled;
    }

    public static function allow(callable $action): mixed
    {
        self::$detectionEnabled = false;

        try {
            return $action();
        } finally {
            self::$detectionEnabled = true;
        }
    }
}
