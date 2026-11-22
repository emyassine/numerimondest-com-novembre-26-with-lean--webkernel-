<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use InvalidArgumentException;

/** Validated request envelope passed between PHP-FPM and a PHP worker. */
final readonly class WorkerRequest
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers,
        public string $body,
        public string $token,
    ) {
        if (preg_match('/\A[A-Z]{1,16}\z/', $method) !== 1
            || ! str_starts_with($path, '/')
            || preg_match('/[\r\n\x00]/', $path) === 1
            || strlen($body) > 8_388_608
            || $token === '') {
            throw new InvalidArgumentException('Invalid compiled worker request.');
        }
        foreach ($headers as $name => $value) {
            if (! is_string($name) || preg_match('/\A[A-Za-z0-9-]{1,64}\z/', $name) !== 1
                || ! is_string($value) || preg_match('/[\r\n\x00]/', $value) === 1 || strlen($value) > 8192) {
                throw new InvalidArgumentException('Invalid compiled worker request header.');
            }
        }
    }
}
