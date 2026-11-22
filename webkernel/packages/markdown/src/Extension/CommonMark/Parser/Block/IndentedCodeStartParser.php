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

use \Webkernel\Markdown\Node\Block\Paragraph;
use \Webkernel\Markdown\Parser\Block\BlockStart;
use \Webkernel\Markdown\Parser\Block\BlockStartParserInterface;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Parser\MarkdownParserStateInterface;

final class IndentedCodeStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if (! $cursor->isIndented()) {
            return BlockStart::none();
        }

        if ($parserState->getActiveBlockParser()->getBlock() instanceof Paragraph) {
            return BlockStart::none();
        }

        if ($cursor->isBlank()) {
            return BlockStart::none();
        }

        $cursor->advanceBy(Cursor::INDENT_LEVEL, true);

        return BlockStart::of(new IndentedCodeParser())->at($cursor);
    }
}
