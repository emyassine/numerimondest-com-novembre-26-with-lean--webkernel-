<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Session;

use InvalidArgumentException;
use SessionHandlerInterface;
use Webkernel\Session\Contracts\Session as SessionContract;

/**
 * Laravel-style session store backed by Webkernel's native session lifecycle.
 *
 * Framework packages such as Livewire and Filament expect a session object
 * with put/get/forget/regenerate semantics. Webkernel's native session is
 * static, so this adapter exposes the same data through an injectable object.
 */
final class NativeStore implements SessionContract
{
    private const string PREVIOUS_URL_KEY = '_previous.url';

    public function start(): void
    {
        Session::start();
    }

    public function save(): void
    {
        Session::save();
    }

    public function getName()
    {
        Session::start();

        return session_name();
    }

    public function setName($name): void
    {
        if (is_string($name) && $name !== '') {
            session_name($name);
        }
    }

    public function getId()
    {
        Session::start();

        return session_id() ?: '';
    }

    public function setId($id): void
    {
        if (is_string($id) && $id !== '') {
            session_id($id);
        }
    }

    public function get($key, $default = null): mixed
    {
        return is_string($key) ? Session::get($key, $default) : $default;
    }

    public function put($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $name => $item) {
                Session::put((string) $name, $item);
            }

            return;
        }

        if (is_string($key)) {
            Session::put($key, $value);
        }
    }

    public function has($key): bool
    {
        return is_string($key) && Session::has($key);
    }

    public function exists($key): bool
    {
        return $this->has($key);
    }

    public function forget($keys): void
    {
        foreach ((array) $keys as $key) {
            if (is_string($key) && $key !== '') {
                Session::forget($key);
            }
        }
    }

    public function remove($key): void
    {
        $this->forget($key);
    }

    public function pull($key, $default = null): mixed
    {
        return is_string($key) ? Session::pull($key, $default) : $default;
    }

    public function push(string $key, mixed $value): void
    {
        $values = Session::get($key, []);
        $values = is_array($values) ? $values : [];
        $values[] = $value;
        Session::put($key, $values);
    }

    public function flash(string|array $key, mixed $value = true): void
    {
        if (is_array($key)) {
            foreach ($key as $name => $item) {
                Session::flash((string) $name, $item);
            }

            return;
        }

        Session::flash($key, $value);
    }

    public function all(): array
    {
        return Session::all();
    }

    public function flush(): void
    {
        foreach (array_keys(Session::all()) as $key) {
            Session::forget((string) $key);
        }
    }

    public function token(): string
    {
        Session::start();

        $tokens = Session::get('_webkernel_csrf', []);
        $tokens = is_array($tokens) ? $tokens : [];

        if (! is_string($tokens['form'] ?? null)) {
            $tokens['form'] = bin2hex(random_bytes(32));
            Session::putReserved('_webkernel_csrf', $tokens);
        }

        return $tokens['form'];
    }

    public function regenerateToken(): void
    {
        Session::start();

        $tokens = Session::get('_webkernel_csrf', []);
        $tokens = is_array($tokens) ? $tokens : [];
        $tokens['form'] = bin2hex(random_bytes(32));
        Session::putReserved('_webkernel_csrf', $tokens);
    }

    public function invalidate(): void
    {
        Session::invalidate();
    }

    public function regenerate($destroy = false): void
    {
        Session::regenerate((bool) $destroy);
    }

    public function migrate($destroy = false): void
    {
        Session::regenerate((bool) $destroy);
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function previousUrl()
    {
        return Session::get(self::PREVIOUS_URL_KEY);
    }

    public function setPreviousUrl($url): void
    {
        Session::put(self::PREVIOUS_URL_KEY, $url);
    }

    public function getHandler(): ?SessionHandlerInterface
    {
        return null;
    }

    public function handlerNeedsRequest(): bool
    {
        return false;
    }

    public function setRequestOnHandler($request): void
    {
    }

    /** @throws InvalidArgumentException */
    public function __call(string $method, array $arguments): mixed
    {
        throw new InvalidArgumentException(sprintf('Session method [%s] is not supported.', $method));
    }
}
