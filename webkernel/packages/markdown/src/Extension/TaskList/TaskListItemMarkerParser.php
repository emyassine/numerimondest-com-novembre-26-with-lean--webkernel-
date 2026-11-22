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

namespace Webkernel\Markdown\Extension\TaskList;

use \Webkernel\Markdown\Extension\CommonMark\Node\Block\ListItem;
use \Webkernel\Markdown\Node\Block\Paragraph;
use \Webkernel\Markdown\Parser\Inline\InlineParserInterface;
use \Webkernel\Markdown\Parser\Inline\InlineParserMatch;
use \Webkernel\Markdown\Parser\InlineParserContext;

final class TaskListItemMarkerParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::oneOf('[ ]', '[x]');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $container = $inlineContext->getContainer();

        // Checkbox must come at the beginning of the first paragraph of the list item
        if ($container->hasChildren() || ! ($container instanceof Paragraph && $container->parent() && $container->parent() instanceof ListItem)) {
            return false;
        }

        $cursor   = $inlineContext->getCursor();
        $oldState = $cursor->saveState();

        $cursor->advanceBy(3);

        if ($cursor->getNextNonSpaceCharacter() === null) {
            $cursor->restoreState($oldState);

            return false;
        }

        $isChecked = $inlineContext->getFullMatch() !== '[ ]';

        $container->appendChild(new TaskListItemMarker($isChecked));

        return true;
    }
}
