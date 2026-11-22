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

namespace Webkernel\Markdown\Parser\Block;

use \Webkernel\Markdown\Node\Block\Paragraph;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Parser\InlineParserEngineInterface;
use \Webkernel\Markdown\Reference\ReferenceInterface;
use \Webkernel\Markdown\Reference\ReferenceParser;

final class ParagraphParser extends AbstractBlockContinueParser implements BlockContinueParserWithInlinesInterface
{
    /** @psalm-readonly */
    private Paragraph $block;

    /** @psalm-readonly */
    private ReferenceParser $referenceParser;

    public function __construct()
    {
        $this->block           = new Paragraph();
        $this->referenceParser = new ReferenceParser();
    }

    public function canHaveLazyContinuationLines(): bool
    {
        return true;
    }

    public function getBlock(): Paragraph
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isBlank()) {
            return BlockContinue::none();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        $this->referenceParser->parse($line);
    }

    public function closeBlock(): void
    {
        $this->block->onlyContainsLinkReferenceDefinitions = $this->referenceParser->hasReferences() && $this->referenceParser->getParagraphContent() === '';
    }

    public function parseInlines(InlineParserEngineInterface $inlineParser): void
    {
        $content = $this->getContentString();
        if ($content !== '') {
            $inlineParser->parse($content, $this->block);
        }
    }

    public function getContentString(): string
    {
        return $this->referenceParser->getParagraphContent();
    }

    /**
     * @return ReferenceInterface[]
     */
    public function getReferences(): iterable
    {
        return $this->referenceParser->getReferences();
    }
}
