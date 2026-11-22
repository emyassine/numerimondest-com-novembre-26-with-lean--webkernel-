<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use InvalidArgumentException;

/** A bounded worker response envelope. */
final readonly class WorkerResponse
{
    /** @param array<string, string> $headers */
    public function __construct(public int $status, public array $headers, public string $body)
    {
        if ($status < 100 || $status > 599 || strlen($body) > 16_777_216) {
            throw new InvalidArgumentException('Invalid compiled worker response.');
        }
        foreach ($headers as $name => $value) {
            if (! is_string($name) || preg_match('/\A[A-Za-z0-9-]{1,64}\z/', $name) !== 1
                || ! is_string($value) || preg_match('/[\r\n\x00]/', $value) === 1 || strlen($value) > 8192) {
                throw new InvalidArgumentException('Invalid compiled worker response header.');
            }
        }
    }
}
