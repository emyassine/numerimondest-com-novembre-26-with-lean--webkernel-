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
use \Webkernel\Markdown\Parser\Block\AbstractBlockContinueParser;
use \Webkernel\Markdown\Parser\Block\BlockContinue;
use \Webkernel\Markdown\Parser\Block\BlockContinueParserInterface;
use \Webkernel\Markdown\Parser\Cursor;
use \Webkernel\Markdown\Util\RegexHelper;

final class HtmlBlockParser extends AbstractBlockContinueParser
{
    /** @psalm-readonly */
    private HtmlBlock $block;

    private string $content = '';

    private bool $finished = false;

    /**
     * @psalm-param HtmlBlock::TYPE_* $blockType
     *
     * @phpstan-param HtmlBlock::TYPE_* $blockType
     */
    public function __construct(int $blockType)
    {
        $this->block = new HtmlBlock($blockType);
    }

    public function getBlock(): HtmlBlock
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($this->finished) {
            return BlockContinue::none();
        }

        if ($cursor->isBlank() && \in_array($this->block->getType(), [HtmlBlock::TYPE_6_BLOCK_ELEMENT, HtmlBlock::TYPE_7_MISC_ELEMENT], true)) {
            return BlockContinue::none();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        if ($this->content !== '') {
            $this->content .= "\n";
        }

        $this->content .= $line;

        // Check for end condition
        // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($this->block->getType() <= HtmlBlock::TYPE_5_CDATA) {
            if (\preg_match(RegexHelper::getHtmlBlockCloseRegex($this->block->getType()), $line) === 1) {
                $this->finished = true;
            }
        }
    }

    public function closeBlock(): void
    {
        $this->block->setLiteral($this->content);
        $this->content = '';
    }
}
