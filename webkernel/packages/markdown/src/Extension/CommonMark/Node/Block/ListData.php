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
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Extension\CommonMark\Node\Block;

class ListData
{
    public ?int $start = null;

    public int $padding = 0;

    /**
     * @psalm-var ListBlock::TYPE_*
     * @phpstan-var ListBlock::TYPE_*
     */
    public string $type;

    /**
     * @psalm-var ListBlock::DELIM_*|null
     * @phpstan-var ListBlock::DELIM_*|null
     */
    public ?string $delimiter = null;

    public ?string $bulletChar = null;

    public int $markerOffset;

    public function equals(ListData $data): bool
    {
        return $this->type === $data->type &&
            $this->delimiter === $data->delimiter &&
            $this->bulletChar === $data->bulletChar;
    }
}
