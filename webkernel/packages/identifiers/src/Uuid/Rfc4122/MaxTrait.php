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

namespace Webkernel\Identifiers\Uuid\Rfc4122;

/**
 * Provides common functionality for max UUIDs
 *
 * @immutable
 */
trait MaxTrait
{
    /**
     * Returns the bytes that comprise the fields
     *
     * @pure
     */
    abstract public function getBytes(): string;

    /**
     * Returns true if the byte string represents a max UUID
     *
     * @pure
     */
    public function isMax(): bool
    {
        return $this->getBytes() === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
    }
}
