<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Bus;

use \Webkernel\Cache\Contracts\LockProvider;
use \Webkernel\Cache\Contracts\Repository as Cache;
use \Webkernel\Queue\Attributes\ReadsQueueAttributes;
use \Webkernel\Queue\Attributes\UniqueFor;

class UniqueLock
{
    use ReadsQueueAttributes;

    /**
     * The cache repository implementation.
     *
     * @var \Webkernel\Cache\Contracts\Repository
     */
    protected $cache;

    /**
     * Create a new unique lock manager instance.
     *
     * @param  \Webkernel\Cache\Contracts\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to acquire a lock for the given job.
     *
     * @param  mixed  $job
     * @return bool
     */
    public function acquire($job)
    {
        $uniqueFor = method_exists($job, 'uniqueFor')
            ? $job->uniqueFor()
            : ($this->getAttributeValue($job, UniqueFor::class, 'uniqueFor') ?? 0);

        $cache = method_exists($job, 'uniqueVia')
            ? ($job->uniqueVia() ?? $this->cache)
            : $this->cache;

        $lock = $cache->lock(self::getKey($job), $uniqueFor);

        if (! $lock->get()) {
            return false;
        }

        if (isset(class_uses_recursive($job)[Queueable::class]) &&
            $cache->getStore() instanceof LockProvider) {
            $job->uniqueLockOwner = $lock->owner();
        }

        return true;
    }

    /**
     * Release the lock for the given job.
     *
     * @param  mixed  $job
     * @return void
     */
    public function release($job)
    {
        $cache = method_exists($job, 'uniqueVia')
            ? ($job->uniqueVia() ?? $this->cache)
            : $this->cache;

        $owner = isset(class_uses_recursive($job)[Queueable::class])
            ? ($job->uniqueLockOwner ?? '')
            : '';

        if (is_string($owner) && $owner !== '') {
            if ($cache->getStore() instanceof LockProvider) {
                $cache->restoreLock(self::getKey($job), $owner)->release();
            }

            return;
        }

        $cache->lock(self::getKey($job))->forceRelease();
    }

    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public static function getKey($job)
    {
        $uniqueId = method_exists($job, 'uniqueId')
            ? $job->uniqueId()
            : ($job->uniqueId ?? '');

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return 'laravel_unique_job:'.$jobName.':'.$uniqueId;
    }
}
