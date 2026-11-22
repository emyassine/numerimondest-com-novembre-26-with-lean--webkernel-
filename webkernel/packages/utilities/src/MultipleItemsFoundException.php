<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Utilities;

use RuntimeException;

class MultipleItemsFoundException extends RuntimeException
{
    /**
     * The number of items found.
     *
     * @var int
     */
    public $count;

    /**
     * Create a new exception instance.
     *
     * @param  int  $count
     * @param  int  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct($count, $code = 0, $previous = null)
    {
        $this->count = $count;

        parent::__construct("$count items were found.", $code, $previous);
    }

    /**
     * Get the number of items found.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
