<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue;

use Closure;
use \Webkernel\Queue\Bus\Batchable;
use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Container\Container;
use \Webkernel\Queue\Contracts\ShouldQueue;
use \Webkernel\Queue\Dispatch\Dispatchable;
use \Webkernel\Serialization\Closure\SerializableClosure;
use ReflectionFunction;

class CallQueuedClosure implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The serializable Closure instance.
     *
     * @var \Webkernel\Serialization\Closure\SerializableClosure
     */
    public $closure;

    /**
     * The name assigned to the job.
     *
     * @var string|null
     */
    public $name = null;

    /**
     * The callbacks that should be executed on failure.
     *
     * @var array
     */
    public $failureCallbacks = [];

    /**
     * Indicate if the job should be deleted when models are missing.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param  \Webkernel\Serialization\Closure\SerializableClosure  $closure
     */
    public function __construct($closure)
    {
        $this->closure = $closure;
    }

    /**
     * Create a new job instance.
     *
     * @param  \Closure  $job
     * @return self
     */
    public static function create(Closure $job)
    {
        return new self(new SerializableClosure($job));
    }

    /**
     * Execute the job.
     *
     * @param  \Webkernel\Container\Container  $container
     * @return void
     */
    public function handle(Container $container)
    {
        $container->call($this->closure->getClosure(), ['job' => $this]);
    }

    /**
     * Add a callback to be executed if the job fails.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function onFailure($callback)
    {
        $this->failureCallbacks[] = $callback instanceof Closure
            ? new SerializableClosure($callback)
            : $callback;

        return $this;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        foreach ($this->failureCallbacks as $callback) {
            $callback($e);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        $closure = $this->closure instanceof SerializableClosure
                    ? $this->closure->getClosure()
                    : $this->closure;

        $reflection = new ReflectionFunction($closure);

        $prefix = is_null($this->name) ? '' : "{$this->name} - ";

        return $prefix.'Closure ('.basename($reflection->getFileName()).':'.$reflection->getStartLine().')';
    }

    /**
     * Assign a name to the job.
     *
     * @param  string  $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }
}
