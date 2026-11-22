<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Mail;

use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Mail\Contracts\Factory as MailFactory;
use \Webkernel\Mail\Contracts\Mailable as MailableContract;
use \Webkernel\Queue\Contracts\ShouldBeEncrypted;
use \Webkernel\Queue\Contracts\ShouldQueueAfterCommit;
use \Webkernel\Queue\Attributes\Backoff;
use \Webkernel\Queue\Attributes\Connection;
use \Webkernel\Queue\Attributes\CountCrashesAsExceptions;
use \Webkernel\Queue\Attributes\DeleteWhenMissingModels;
use \Webkernel\Queue\Attributes\FailOnTimeout;
use \Webkernel\Queue\Attributes\MaxExceptions;
use \Webkernel\Queue\Attributes\Queue as QueueAttribute;
use \Webkernel\Queue\Attributes\ReadsQueueAttributes;
use \Webkernel\Queue\Attributes\Timeout;
use \Webkernel\Queue\Attributes\Tries;
use \Webkernel\Queue\InteractsWithQueue;

class SendQueuedMailable
{
    use InteractsWithQueue, Queueable, ReadsQueueAttributes;

    /**
     * The mailable message instance.
     *
     * @var \Webkernel\Mail\Contracts\Mailable
     */
    public $mailable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int|null
     */
    public $maxExceptions;

    /**
     * Indicates if the job should be encrypted.
     *
     * @var bool
     */
    public $shouldBeEncrypted = false;

    /**
     * Indicates if the job should be deleted when models are missing.
     */
    public bool $deleteWhenMissingModels = false;

    /**
     * Indicates if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = false;

    /**
     * Indicates if worker crashes should count towards the maximum exceptions.
     *
     * @var bool
     */
    public $countCrashesAsExceptions = false;

    /**
     * Create a new job instance.
     *
     * @param  \Webkernel\Mail\Contracts\Mailable  $mailable
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;

        if ($mailable instanceof ShouldQueueAfterCommit) {
            $this->afterCommit = true;
        } else {
            $this->afterCommit = property_exists($mailable, 'afterCommit') ? $mailable->afterCommit : null;
        }

        $this->connection = $this->getAttributeValue($mailable, Connection::class, 'connection');
        $this->maxExceptions = $this->getAttributeValue($mailable, MaxExceptions::class, 'maxExceptions');
        $this->queue = $this->getAttributeValue($mailable, QueueAttribute::class, 'queue');
        $this->shouldBeEncrypted = $mailable instanceof ShouldBeEncrypted;
        $this->timeout = $this->getAttributeValue($mailable, Timeout::class, 'timeout');
        $this->tries = $this->getAttributeValue($mailable, Tries::class, 'tries');
        $this->deleteWhenMissingModels = $this->getAttributeValue($mailable, DeleteWhenMissingModels::class, 'deleteWhenMissingModels') ?? false;
        $this->failOnTimeout = $this->getAttributeValue($mailable, FailOnTimeout::class, 'failOnTimeout') ?? false;
        $this->countCrashesAsExceptions = $this->getAttributeValue($mailable, CountCrashesAsExceptions::class, 'countCrashesAsExceptions') ?? false;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Webkernel\Mail\Contracts\Factory  $factory
     * @return void
     */
    public function handle(MailFactory $factory)
    {
        $this->mailable->send($factory);
    }

    /**
     * Get the number of seconds before a released mailable will be available.
     *
     * @return mixed
     */
    public function backoff()
    {
        $backoff = $this->getAttributeValue($this->mailable, Backoff::class, 'backoff');

        if (method_exists($this->mailable, 'backoff')) {
            $backoff = $this->mailable->backoff();
        }

        return $backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime|null
     */
    public function retryUntil()
    {
        if (! method_exists($this->mailable, 'retryUntil') && ! isset($this->mailable->retryUntil)) {
            return;
        }

        return $this->mailable->retryUntil ?? $this->mailable->retryUntil();
    }

    /**
     * Call the failed method on the mailable instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->mailable, 'failed')) {
            $this->mailable->failed($e);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->mailable);
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->mailable = clone $this->mailable;
    }
}
