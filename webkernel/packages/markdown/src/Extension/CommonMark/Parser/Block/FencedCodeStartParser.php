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

use \Webkernel\Markdown\Parser\Block\BlockStart;
use \Webkernel\Markdown\Parser\Block\BlockStartParserInterface;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Parser\MarkdownParserStateInterface;

final class FencedCodeStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || ! \in_array($cursor->getNextNonSpaceCharacter(), ['`', '~'], true)) {
            return BlockStart::none();
        }

        $indent = $cursor->getIndent();
        $fence  = $cursor->matchInPlace('/\G[ \t]*(?:`{3,}+(?!.*`)|~{3,})/');
        if ($fence === null) {
            return BlockStart::none();
        }

        // fenced code block
        $fence = \ltrim($fence, " \t");

        return BlockStart::of(new FencedCodeParser(\strlen($fence), $fence[0], $indent))->at($cursor);
    }
}
