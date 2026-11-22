<?php

namespace Webkernel\Liveview\Mechanisms\HandleComponents;

use \Webkernel\Cache\Limiter as RateLimiter;
use \Webkernel\Config\Config;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

use function Webkernel\Liveview\trigger;

class Checksum {
    protected static $maxFailures = 10;
    protected static $decaySeconds = 600; // 10 minutes
    protected static $rateLimitingEnabledForTesting = false;
    protected static bool $rateLimitChecked = false;

    static function verify($snapshot) {
        // Check if this IP is already blocked due to too many failures
        static::enforceRateLimit();

        $checksum = $snapshot['checksum'];

        unset($snapshot['checksum']);

        trigger('checksum.verify', $checksum, $snapshot);

        if (! hash_equals($comparitor = self::generate($snapshot), $checksum)) {
            trigger('checksum.fail', $checksum, $comparitor, $snapshot);

            static::recordFailure();

            throw new CorruptComponentPayloadException;
        }
    }

    static function enableRateLimitingForTesting()
    {
        static::$rateLimitingEnabledForTesting = true;
    }

    static function disableRateLimitingForTesting()
    {
        static::$rateLimitingEnabledForTesting = false;
    }

    protected static function enforceRateLimit()
    {
        if (Config::get('app.env') === 'testing' && ! static::$rateLimitingEnabledForTesting) return;

        $request = request();

        // Only check the rate limit once per request (not once per component)
        if (static::$rateLimitChecked) {
            return;
        }

        $key = static::rateLimitKey();

        if (RateLimiter::tooManyAttempts($key, static::$maxFailures)) {
            $seconds = RateLimiter::availableIn($key);

            throw new TooManyRequestsHttpException(
                $seconds,
                'Too many invalid Livewire requests. Please try again later.'
            );
        }

        static::$rateLimitChecked = true;
    }

    protected static function recordFailure()
    {
        if (Config::get('app.env') === 'testing' && ! static::$rateLimitingEnabledForTesting) return;

        RateLimiter::hit(static::rateLimitKey(), static::$decaySeconds);
    }

    protected static function rateLimitKey(): string
    {
        return 'livewire-checksum-failures:' . request()->ip();
    }

    static function generate($snapshot) {
        $hashKey = app('encrypter')->getKey();

        // Remove the children from the memo in the snapshot, as it is actually Ok
        // if the "children" tracking is tampered with. This way JavaScript can
        // modify children as it needs to for dom-diffing purposes...
        unset($snapshot['memo']['children']);
        
        $checksum = hash_hmac('sha256', json_encode($snapshot), $hashKey);

        trigger('checksum.generate', $checksum, $snapshot);

        return $checksum;
    }
}
