<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Features\SupportRateLimiting;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Route\Route;

trait WithRateLimiting
{
    protected function clearRateLimiter(?string $method = null, ?string $component = null): void
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $component ??= static::class;
        RateLimiter::clear($this->getRateLimitKey($method, $component));
    }

    protected function getRateLimitKey(?string $method = null, ?string $component = null): string
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $component ??= static::class;
        $ip = Route::currentRequest()->ip() ?? 'unknown';

        return 'livewire-rate-limiter:' . hash('sha256', $component . '|' . $method . '|' . $ip);
    }

    protected function hitRateLimiter(?string $method = null, int $decay_seconds = 60, ?string $component = null): void
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $component ??= static::class;
        RateLimiter::hit($this->getRateLimitKey($method, $component), $decay_seconds);
    }

    protected function rateLimit(int $max_attempts, int $decay_seconds = 60, ?string $method = null, ?string $component = null): void
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $component ??= static::class;
        $key = $this->getRateLimitKey($method, $component);

        if ($this->isRateLimited($max_attempts, $method, $component)) {
            throw new TooManyRequestsException(
                $component,
                $method,
                Route::currentRequest()->ip(),
                RateLimiter::availableIn($key),
            );
        }

        RateLimiter::hit($key, $decay_seconds);
    }

    protected function isRateLimited(int $max_attempts, ?string $method = null, ?string $component = null): bool
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $component ??= static::class;

        return RateLimiter::tooManyAttempts($this->getRateLimitKey($method, $component), $max_attempts);
    }
}
