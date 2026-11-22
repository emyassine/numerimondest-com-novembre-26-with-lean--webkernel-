<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use RuntimeException;
use Throwable;

class MultipleRecordsFoundException extends RuntimeException
{
    public function __construct(public int $count, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($count.' records were found.', $code, $previous);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
