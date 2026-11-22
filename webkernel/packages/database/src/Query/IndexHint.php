<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Database\Query;

class IndexHint
{
    /**
     * The type of query hint.
     *
     * @var string
     */
    public $type;

    /**
     * The name of the index.
     *
     * @var string
     */
    public $index;

    /**
     * Create a new index hint instance.
     *
     * @param  string  $type
     * @param  string  $index
     */
    public function __construct($type, $index)
    {
        $this->type = $type;
        $this->index = $index;
    }
}
