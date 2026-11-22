<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Parser;

/**
 * Encapsulates the current state of a cursor in case you need to rollback later.
 *
 * WARNING: Do not attempt to use this class for ANYTHING except for
 * type hinting and passing this object back into restoreState().
 * The constructor, methods, and inner contents may change in any
 * future release without warning!
 *
 * @internal
 *
 * @psalm-immutable
 */
final class CursorState
{
    /**
     * @var array<int, mixed>
     *
     * @psalm-readonly
     */
    private array $state;

    /**
     * @internal
     *
     * @param array<int, mixed> $state
     */
    public function __construct(array $state)
    {
        $this->state = $state;
    }

    /**
     * @internal
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->state;
    }
}
