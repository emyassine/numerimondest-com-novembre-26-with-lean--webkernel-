<?php

namespace Webkernel\Liveview\Mechanisms\HandleComponents;

class SecurityPolicy
{
    /**
     * Classes that should never be instantiated by Livewire synthesizers.
     * These are known-dangerous classes that could be exploited if an attacker
     * somehow bypassed the checksum protection.
     */
    protected static $deniedClasses = [
        // Console commands - could execute arbitrary system commands
        '\Webkernel\Console\Command',
        'Symfony\Component\Console\Command\Command',

        // Process execution - direct system command execution
        'Symfony\Component\Process\Process',

        // Known serialization gadgets
        '\Webkernel\Broadcasting\PendingBroadcast',
        '\Webkernel\Broadcasting\BroadcastEvent',
        '\Webkernel\Testing\PendingCommand',

        // Queue jobs - could execute arbitrary code
        '\Webkernel\Queue\CallQueuedClosure',
        '\Webkernel\Queue\Jobs\Job',

        // Mail - could send arbitrary emails
        '\Webkernel\Mail\Mailable',

        // Notifications - could send arbitrary notifications
        '\Webkernel\Notifications\Notification',

        // Known exploit chain gadgets (Synacktiv disclosure)
        'GuzzleHttp\Psr7\FnStream',
        'League\Flysystem\UrlGeneration\ShardedPrefixPublicUrlGenerator',
    ];

    /**
     * Validate that a class is safe to instantiate.
     * Throws an exception if the class is in the denylist.
     */
    public static function validateClass(string $class): void
    {
        foreach (static::$deniedClasses as $denied) {
            if (is_a($class, $denied, true)) {
                throw new \Exception("Livewire: Class [{$class}] is not allowed to be instantiated.");
            }
        }
    }

    /**
     * Add classes to the denylist at runtime.
     */
    public static function denyClasses(array $classes): void
    {
        static::$deniedClasses = array_merge(static::$deniedClasses, $classes);
    }

    /**
     * Get the current denylist (useful for testing).
     */
    public static function getDeniedClasses(): array
    {
        return static::$deniedClasses;
    }
}
