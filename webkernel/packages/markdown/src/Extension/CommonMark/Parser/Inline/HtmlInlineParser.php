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

namespace Webkernel\Markdown\Extension\CommonMark\Parser\Inline;

use \Webkernel\Markdown\Extension\CommonMark\Node\Inline\HtmlInline;
use \Webkernel\Markdown\Parser\Inline\InlineParserInterface;
use \Webkernel\Markdown\Parser\Inline\InlineParserMatch;
use \Webkernel\Markdown\Parser\InlineParserContext;
use \Webkernel\Markdown\Util\RegexHelper;

final class HtmlInlineParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex(RegexHelper::PARTIAL_HTMLTAG)->caseSensitive();
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $inline = $inlineContext->getFullMatch();

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new HtmlInline($inline));

        return true;
    }
}
