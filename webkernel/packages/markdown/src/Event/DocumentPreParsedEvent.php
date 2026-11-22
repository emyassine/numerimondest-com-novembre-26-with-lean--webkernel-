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

namespace Webkernel\Markdown\Event;

use \Webkernel\Markdown\Input\MarkdownInputInterface;
use \Webkernel\Markdown\Node\Block\Document;

/**
 * Event dispatched when the document is about to be parsed
 */
final class DocumentPreParsedEvent extends AbstractEvent
{
    /** @psalm-readonly */
    private Document $document;

    private MarkdownInputInterface $markdown;

    public function __construct(Document $document, MarkdownInputInterface $markdown)
    {
        $this->document = $document;
        $this->markdown = $markdown;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getMarkdown(): MarkdownInputInterface
    {
        return $this->markdown;
    }

    public function replaceMarkdown(MarkdownInputInterface $markdownInput): void
    {
        $this->markdown = $markdownInput;
    }
}
