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

namespace Webkernel\Markdown;

use \Webkernel\Markdown\Exception\CommonMarkException;
use \Webkernel\Markdown\Output\RenderedContentInterface;

/**
 * Interface for a service which converts Markdown to HTML.
 *
 * @deprecated since 2.2; use {@link ConverterInterface} instead
 */
interface MarkdownConverterInterface
{
    /**
     * Converts Markdown to HTML.
     *
     * @deprecated since 2.2; use {@link ConverterInterface::convert()} instead
     *
     * @throws CommonMarkException
     */
    public function convertToHtml(string $markdown): RenderedContentInterface;
}
