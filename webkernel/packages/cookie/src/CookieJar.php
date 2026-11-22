<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Webkernel\Config\Config;
use Webkernel\Cookie\Contracts\QueueingFactory;

/** Creates and queues Symfony cookies for the current response. */
final class CookieJar implements QueueingFactory
{
    /** @var array<string, Cookie> */
    private array $queued = [];

    private function path(): string
    {
        $path = Config::get('session.path', '/');

        return is_string($path) && $path !== '' ? $path : '/';
    }

    private function domain(): ?string
    {
        $domain = Config::get('session.domain');

        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    private function secure(): bool
    {
        $secure = Config::get('session.secure');

        return is_bool($secure) ? $secure : false;
    }

    private function sameSite(): string
    {
        $same_site = Config::get('session.same_site', 'Lax');

        return is_string($same_site) && $same_site !== '' ? $same_site : 'Lax';
    }

    public function make(
        $name,
        $value,
        $minutes = 0,
        $path = null,
        $domain = null,
        $secure = null,
        $httpOnly = true,
        $raw = false,
        $sameSite = null,
    ): Cookie {
        $expires = is_int($minutes) && $minutes > 0 ? time() + ($minutes * 60) : 0;

        return new Cookie(
            (string) $name,
            (string) $value,
            $expires,
            $path ?? $this->path(),
            $domain ?? $this->domain(),
            $secure ?? $this->secure(),
            (bool) $httpOnly,
            (bool) $raw,
            $sameSite ?? $this->sameSite(),
        );
    }

    public function forever(
        $name,
        $value,
        $path = null,
        $domain = null,
        $secure = null,
        $httpOnly = true,
        $raw = false,
        $sameSite = null,
    ): Cookie {
        return $this->make($name, $value, 2628000, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    public function forget($name, $path = null, $domain = null): Cookie
    {
        return $this->make($name, '', -2628000, $path, $domain);
    }

    public function queue(...$parameters): void
    {
        $cookie = $parameters[0] ?? null;

        if ($cookie instanceof Cookie) {
            $this->queued[$cookie->getName()] = $cookie;

            return;
        }

        $this->queued[(string) $cookie] = $this->make(...$parameters);
    }

    public function unqueue($name, $path = null): void
    {
        unset($this->queued[(string) $name]);
    }

    /** @return array<string, Cookie> */
    public function getQueuedCookies(): array
    {
        return $this->queued;
    }
}
