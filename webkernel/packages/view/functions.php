<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\View\View;
use Webkernel\Utilities\Contracts\Htmlable;
use Webkernel\View\ViewResult;

if (! function_exists('view')) {
    /** @param $name string View name. @param $data array<string, mixed> Template data. */
    function view(string $name, array $data = []): ViewResult
    {
        return View::make($name, $data);
    }
}

if (! function_exists('e')) {
    /** Escapes a value for an HTML text or attribute context. */
    function e(mixed $value, bool $doubleEncode = true): string
    {
        if ($value instanceof Htmlable) { return (string) $value->toHtml(); }
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}
