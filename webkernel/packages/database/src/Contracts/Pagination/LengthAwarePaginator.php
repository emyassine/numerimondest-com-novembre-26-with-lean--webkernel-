<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Database\Contracts\Pagination;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @extends Paginator<TKey, TValue>
 */
interface LengthAwarePaginator extends Paginator
{
    /**
     * Create a range of pagination URLs.
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end);

    /**
     * Determine the total number of items in the data store.
     *
     * @return int
     */
    public function total();

    /**
     * Get the page number of the last available page.
     *
     * @return int
     */
    public function lastPage();
}
