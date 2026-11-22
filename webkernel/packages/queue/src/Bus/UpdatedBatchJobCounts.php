<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Bus;

class UpdatedBatchJobCounts
{
    /**
     * The number of pending jobs remaining for the batch.
     *
     * @var int
     */
    public $pendingJobs;

    /**
     * The number of failed jobs that belong to the batch.
     *
     * @var int
     */
    public $failedJobs;

    /**
     * Create a new batch job counts object.
     *
     * @param  int  $pendingJobs
     * @param  int  $failedJobs
     */
    public function __construct(int $pendingJobs = 0, int $failedJobs = 0)
    {
        $this->pendingJobs = $pendingJobs;
        $this->failedJobs = $failedJobs;
    }

    /**
     * Determine if all jobs have run exactly once.
     *
     * @return bool
     */
    public function allJobsHaveRanExactlyOnce()
    {
        return ($this->pendingJobs - $this->failedJobs) === 0;
    }
}
