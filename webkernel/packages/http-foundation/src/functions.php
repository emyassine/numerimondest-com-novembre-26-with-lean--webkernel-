<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\HttpFoundation\Exceptions\HttpResponseException;
use Webkernel\HttpFoundation\Response;

if (! function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new HttpResponseException(new Response($message, $status));
    }
}

if (! function_exists('abort_unless')) {
    function abort_unless(mixed $condition, int $status, string $message = ''): void
    {
        if (! $condition) {
            abort($status, $message);
        }
    }
}
