<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

if (! function_exists('route')) {
    /**
     * Generate a URI for a named route.
     *
     * @param array<string, string> $parameters
     */
    function route(string $name, array $parameters = []): string
    {
        return \Webkernel\Route\Route::url($name, $parameters);
    }
}

if (! function_exists('request')) {
    /** Return the current Webkernel request, or one input value when given a key. */
    function request(string|array|null $key = null, mixed $default = null): mixed
    {
        $request = \Webkernel\Route\Route::currentRequest();
        if ($key === null) { return $request; }
        if (is_array($key)) {
            return array_intersect_key($request->all(), array_fill_keys($key, true));
        }

        return $request->input($key, $default);
    }
}

if (! function_exists('original_request')) {
    /** Return the original Webkernel request for code that reads route state. */
    function original_request(): \Webkernel\Route\Request
    {
        return \Webkernel\Route\Route::currentRequest();
    }
}
