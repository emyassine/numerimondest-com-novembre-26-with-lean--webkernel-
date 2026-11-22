<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Webkernel\Identifiers\Uuid\Generator;

use \Webkernel\Identifiers\Uuid\Type\Hexadecimal;

/**
 * A time generator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
interface TimeGeneratorInterface
{
    /**
     * Generate a binary string from a node ID, clock sequence, and current time
     *
     * @param Hexadecimal | int | string | null $node A 48-bit number representing the hardware address; this number may
     *     be represented as an integer or a hexadecimal string
     * @param int | null $clockSeq A 14-bit number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or if the node ID changes
     *
     * @return string A binary string
     */
    public function generate($node = null, ?int $clockSeq = null): string;
}
