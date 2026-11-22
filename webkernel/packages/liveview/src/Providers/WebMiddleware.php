<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Providers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkernel\Csrf;
use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;
use Webkernel\Session\Session;

/** Webkernel session and CSRF middleware for Livewire/Filament web routes. */
final class WebMiddleware implements RouteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        Session::start();

        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $formId = $request->input('_csrf_id', 'form');
            $token = $request->input('_token', $request->header('x-csrf-token', ''));
            if (! is_string($formId) || ! is_string($token) || ! Csrf::verify($token, $formId)) {
                throw new HttpException(419, 'CSRF token mismatch.');
            }
        }

        return $next();
    }
}
