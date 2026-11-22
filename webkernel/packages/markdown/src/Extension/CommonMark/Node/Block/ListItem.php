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
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Extension\CommonMark\Node\Block;

use \Webkernel\Markdown\Node\Block\AbstractBlock;

class ListItem extends AbstractBlock
{
    /** @psalm-readonly */
    protected ListData $listData;

    public function __construct(ListData $listData)
    {
        parent::__construct();

        $this->listData = $listData;
    }

    public function getListData(): ListData
    {
        return $this->listData;
    }
}
