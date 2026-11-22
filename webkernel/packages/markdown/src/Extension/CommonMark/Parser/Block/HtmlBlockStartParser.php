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

use \Webkernel\Markdown\Extension\CommonMark\Node\Block\HtmlBlock;
use \Webkernel\Markdown\Node\Block\Paragraph;
use \Webkernel\Markdown\Parser\Block\BlockStart;
use \Webkernel\Markdown\Parser\Block\BlockStartParserInterface;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Parser\MarkdownParserStateInterface;
use \Webkernel\Markdown\Util\RegexHelper;

final class HtmlBlockStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '<') {
            return BlockStart::none();
        }

        $tmpCursor = clone $cursor;
        $tmpCursor->advanceToNextNonSpaceOrTab();
        $line = $tmpCursor->getRemainder();

        for ($blockType = 1; $blockType <= 7; $blockType++) {
            /** @psalm-var HtmlBlock::TYPE_* $blockType */
            /** @phpstan-var HtmlBlock::TYPE_* $blockType */
            $match = RegexHelper::matchAt(
                RegexHelper::getHtmlBlockOpenRegex($blockType),
                $line
            );

            if ($match !== null && ($blockType < 7 || $this->isType7BlockAllowed($cursor, $parserState))) {
                return BlockStart::of(new HtmlBlockParser($blockType))->at($cursor);
            }
        }

        return BlockStart::none();
    }

    private function isType7BlockAllowed(Cursor $cursor, MarkdownParserStateInterface $parserState): bool
    {
        // Type 7 blocks can't interrupt paragraphs
        if ($parserState->getLastMatchedBlockParser()->getBlock() instanceof Paragraph) {
            return false;
        }

        // Even lazy ones
        return ! $parserState->getActiveBlockParser()->canHaveLazyContinuationLines();
    }
}
