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

/**
 * Trait MagicParameter.
 *
 * Allows to retrieve parameter in magic calls by index or name.
 */
trait MagicParameter
{
    private function getMagicParameter(array $parameters, int $index, string $key, $default)
    {
        if (\array_key_exists($index, $parameters)) {
            return $parameters[$index];
        }

        if (\array_key_exists($key, $parameters)) {
            return $parameters[$key];
        }

        return $default;
    }
}
