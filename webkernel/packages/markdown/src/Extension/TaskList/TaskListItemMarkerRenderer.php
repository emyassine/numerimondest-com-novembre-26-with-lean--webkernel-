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

use \Webkernel\Markdown\Node\Node;
use \Webkernel\Markdown\Renderer\ChildNodeRendererInterface;
use \Webkernel\Markdown\Renderer\NodeRendererInterface;
use \Webkernel\Markdown\Util\HtmlElement;
use \Webkernel\Markdown\Xml\XmlNodeRendererInterface;

final class TaskListItemMarkerRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    /**
     * @param TaskListItemMarker $node
     *
     * {@inheritDoc}
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        TaskListItemMarker::assertInstanceOf($node);

        $attrs    = $node->data->get('attributes');
        $checkbox = new HtmlElement('input', $attrs, '', true);

        if ($node->isChecked()) {
            $checkbox->setAttribute('checked', '');
        }

        $checkbox->setAttribute('disabled', '');
        $checkbox->setAttribute('type', 'checkbox');

        return $checkbox;
    }

    public function getXmlTagName(Node $node): string
    {
        return 'task_list_item_marker';
    }

    /**
     * @param TaskListItemMarker $node
     *
     * @return array<string, scalar>
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function getXmlAttributes(Node $node): array
    {
        TaskListItemMarker::assertInstanceOf($node);

        if ($node->isChecked()) {
            return ['checked' => 'checked'];
        }

        return [];
    }
}
