<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\Accounts\AuthManager;
use Webkernel\Accounts\Contracts\Guard;
use Webkernel\Csrf;
use Webkernel\Route\Redirector;
use Webkernel\Route\UrlGenerator;
use Webkernel\Session\NativeStore;

if (! function_exists('auth')) {
    /**
     * Resolves the authentication factory, or one of its guards.
     *
     * @return ($guard is null ? AuthManager : Guard)
     */
    function auth(?string $guard = null): AuthManager | Guard
    {
        $auth = app(AuthManager::class);

        return $guard === null ? $auth : $auth->guard($guard);
    }
}

if (! function_exists('url')) {
    function url(string $path = '', array $extra = [], ?bool $secure = null): string
    {
        return app(UrlGenerator::class)->to($path, $extra, $secure);
    }
}

if (! function_exists('route')) {
    function route(string|\BackedEnum $name, array $parameters = [], bool $absolute = true): string
    {
        return app(UrlGenerator::class)->route($name, $parameters, $absolute);
    }
}

if (! function_exists('redirect')) {
    function redirect(?string $to = null): Redirector|\Webkernel\HttpFoundation\RedirectResponse
    {
        $redirector = app(Redirector::class);
        $redirector->setSession(session());

        return $to === null ? $redirector : $redirector->to($to);
    }
}

if (! function_exists('back')) {
    function back(int $status = 302, array $headers = [], mixed $fallback = false): \Webkernel\HttpFoundation\RedirectResponse
    {
        return redirect()->back($status, $headers, $fallback);
    }
}

if (! function_exists('event')) {
    /**
     * Dispatches an event object or class name through the Webkernel event dispatcher.
     */
    function event(object|string $event, array $payload = [], bool $halt = false): mixed
    {
        return app(\Webkernel\Events\Dispatcher::class)->dispatch($event, $payload, $halt);
    }
}

if (! function_exists('session')) {
    function session(): NativeStore
    {
        return app('session.store');
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token('form');
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): \Webkernel\CsrfField
    {
        return Csrf::field('form');
    }
}
