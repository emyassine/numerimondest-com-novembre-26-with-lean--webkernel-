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

namespace Webkernel\Markdown\Extension\CommonMark\Parser\Block;

use \Webkernel\Markdown\Extension\CommonMark\Node\Block\ThematicBreak;
use \Webkernel\Markdown\Parser\Block\AbstractBlockContinueParser;
use \Webkernel\Markdown\Parser\Block\BlockContinue;
use \Webkernel\Markdown\Parser\Block\BlockContinueParserInterface;
use \Webkernel\Markdown\Parser\Cursor;

final class ThematicBreakParser extends AbstractBlockContinueParser
{
    /** @psalm-readonly */
    private ThematicBreak $block;

    public function __construct()
    {
        $this->block = new ThematicBreak();
    }

    public function getBlock(): ThematicBreak
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        // a horizontal rule can never container > 1 line, so fail to match
        return BlockContinue::none();
    }
}
