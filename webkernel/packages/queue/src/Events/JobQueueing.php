<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Events;

class JobQueueing
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string|null  $queue  The queue name.
     * @param  \Closure|string|object  $job  The job instance.
     * @param  string  $payload  The job payload.
     * @param  int|null  $delay  The number of seconds the job was delayed.
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $job,
        public $payload,
        public $delay,
    ) {
    }

    /**
     * Get the decoded job payload.
     *
     * @return array
     *
     * @throws \JsonException
     */
    public function payload()
    {
        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
    }
}
