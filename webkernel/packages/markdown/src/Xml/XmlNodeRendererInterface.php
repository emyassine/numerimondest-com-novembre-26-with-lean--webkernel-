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

namespace Webkernel\Markdown\Xml;

use \Webkernel\Markdown\Node\Node;

interface XmlNodeRendererInterface
{
    public function getXmlTagName(Node $node): string;

    /**
     * @return array<string, string|int|float|bool>
     *
     * @psalm-return array<string, scalar>
     */
    public function getXmlAttributes(Node $node): array;
}
