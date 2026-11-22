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

use \Webkernel\Markdown\Extension\CommonMark\Node\Block\IndentedCode;
use \Webkernel\Markdown\Parser\Block\AbstractBlockContinueParser;
use \Webkernel\Markdown\Parser\Block\BlockContinue;
use \Webkernel\Markdown\Parser\Block\BlockContinueParserInterface;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Util\ArrayCollection;

final class IndentedCodeParser extends AbstractBlockContinueParser
{
    /** @psalm-readonly */
    private IndentedCode $block;

    /** @var ArrayCollection<string> */
    private ArrayCollection $strings;

    public function __construct()
    {
        $this->block   = new IndentedCode();
        $this->strings = new ArrayCollection();
    }

    public function getBlock(): IndentedCode
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isIndented()) {
            $cursor->advanceBy(Cursor::INDENT_LEVEL, true);

            return BlockContinue::at($cursor);
        }

        if ($cursor->isBlank()) {
            $cursor->advanceToNextNonSpaceOrTab();

            return BlockContinue::at($cursor);
        }

        return BlockContinue::none();
    }

    public function addLine(string $line): void
    {
        $this->strings[] = $line;
    }

    public function closeBlock(): void
    {
        $lines = $this->strings->toArray();

        // Note that indented code block cannot be empty, so $lines will always have at least one non-empty element
        while (\preg_match('/^[ \t]*$/', \end($lines))) { // @phpstan-ignore-line
            \array_pop($lines);
        }

        $this->block->setLiteral(\implode("\n", $lines) . "\n");
        $this->block->setEndLine($this->block->getStartLine() + \count($lines) - 1);
    }
}
