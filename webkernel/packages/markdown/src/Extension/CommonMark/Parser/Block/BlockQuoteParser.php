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

use \Webkernel\Markdown\Extension\CommonMark\Node\Block\BlockQuote;
use \Webkernel\Markdown\Node\Block\AbstractBlock;
use \Webkernel\Markdown\Parser\Block\AbstractBlockContinueParser;
use \Webkernel\Markdown\Parser\Block\BlockContinue;
use \Webkernel\Markdown\Parser\Block\BlockContinueParserInterface;
use \Webkernel\Markdown\Parser\Cursor;

final class BlockQuoteParser extends AbstractBlockContinueParser
{
    /** @psalm-readonly */
    private BlockQuote $block;

    public function __construct()
    {
        $this->block = new BlockQuote();
    }

    public function getBlock(): BlockQuote
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if (! $cursor->isIndented() && $cursor->getNextNonSpaceCharacter() === '>') {
            $cursor->advanceToNextNonSpaceOrTab();
            $cursor->advanceBy(1);
            $cursor->advanceBySpaceOrTab();

            return BlockContinue::at($cursor);
        }

        return BlockContinue::none();
    }
}
