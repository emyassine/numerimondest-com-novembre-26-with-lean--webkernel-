<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Features\SupportRateLimiting\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class TooManyRequestsException extends HttpException
{
    public readonly int $minutesUntilAvailable;

    public function __construct(
        public readonly string $component,
        public readonly string $method,
        public readonly ?string $ip,
        public readonly int $secondsUntilAvailable,
    ) {
        $this->minutesUntilAvailable = (int) ceil($this->secondsUntilAvailable / 60);

        parent::__construct(429, sprintf(
            'Too many requests from [%s] to method [%s] on component: [%s]. Retry in %d seconds.',
            $this->ip ?? 'unknown',
            $this->method,
            $this->component,
            $this->secondsUntilAvailable,
        ));
    }
}
