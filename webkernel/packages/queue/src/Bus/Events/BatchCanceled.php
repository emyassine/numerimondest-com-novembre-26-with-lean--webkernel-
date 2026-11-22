<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Bus\Events;

use \Webkernel\Queue\Bus\Batch;
use Throwable;

class BatchCanceled
{
    /**
     * Create a new event instance.
     *
     * @param  \Webkernel\Queue\Bus\Batch  $batch  The batch instance.
     * @param  \Throwable|null  $exception  The exception that caused the cancellation.
     */
    public function __construct(
        public Batch $batch,
        public ?Throwable $exception = null,
    ) {
    }
}
