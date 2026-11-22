<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Jobs;

use \Webkernel\Queue\Contracts\Job as JobContract;
use \Webkernel\Utilities\Str;

class FakeJob extends Job implements JobContract
{
    /**
     * The number of seconds the released job was delayed.
     *
     * @var int
     */
    public $releaseDelay;

    /**
     * The number of attempts made to process the job.
     *
     * @var int
     */
    public $attempts = 1;

    /**
     * The exception the job failed with.
     *
     * @var \Throwable
     */
    public $failedWith;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return once(fn () => (string) Str::uuid());
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return '';
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->released = true;
        $this->releaseDelay = $delay;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->attempts;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    public function fail($e = null)
    {
        $this->failed = true;
        $this->failedWith = $e;
    }
}
