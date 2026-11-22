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

declare(strict_types=1);

namespace Webkernel\Markdown\Event;

use \Webkernel\Markdown\Output\RenderedContentInterface;

final class DocumentRenderedEvent extends AbstractEvent
{
    private RenderedContentInterface $output;

    public function __construct(RenderedContentInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOutput(): RenderedContentInterface
    {
        return $this->output;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function replaceOutput(RenderedContentInterface $output): void
    {
        $this->output = $output;
    }
}
