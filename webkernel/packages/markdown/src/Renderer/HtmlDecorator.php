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

namespace Webkernel\Markdown\Renderer;

use \Webkernel\Markdown\Node\Node;
use \Webkernel\Markdown\Util\HtmlElement;

final class HtmlDecorator implements NodeRendererInterface
{
    private NodeRendererInterface $inner;
    private string $tag;
    /** @var array<string, string|string[]|bool> */
    private array $attributes;
    private bool $selfClosing;

    /**
     * @param array<string, string|string[]|bool> $attributes
     */
    public function __construct(NodeRendererInterface $inner, string $tag, array $attributes = [], bool $selfClosing = false)
    {
        $this->inner       = $inner;
        $this->tag         = $tag;
        $this->attributes  = $attributes;
        $this->selfClosing = $selfClosing;
    }

    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        return new HtmlElement($this->tag, $this->attributes, $this->inner->render($node, $childRenderer), $this->selfClosing);
    }
}
