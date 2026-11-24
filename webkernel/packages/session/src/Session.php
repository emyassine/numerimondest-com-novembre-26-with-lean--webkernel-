<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Session;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\Config\Config;

/** Manages the native session store without starting sessions on ordinary requests. */
final class Session
{
    private const string FLASH_KEY = '_webkernel_flash';

    /** @var array<string, mixed>|null */
    private static ?array $configuration = null;

    private function __construct()
    {
    }

    /** @param array<string, mixed> $configuration */
    public static function configure(array $configuration): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session configuration cannot change after a session starts.');
        }
        self::$configuration = $configuration;
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            throw new RuntimeException('A session cannot start after response headers are sent.');
        }

        $configuration = self::$configuration ?? Config::get('session', []);
        if (! is_array($configuration)) {
            throw new InvalidArgumentException('Session configuration must be an array.');
        }
        $name = $configuration['cookie'] ?? 'WEBKERNEL_SESSID';
        // Leave session storage to PHP-FPM unless the application explicitly overrides it.
        $save_path = $configuration['save_path'] ?? null;
        $lifetime = $configuration['lifetime'] ?? 120;
        $same_site = $configuration['same_site'] ?? 'Lax';
        $http_only = $configuration['http_only'] ?? true;
        $secure = $configuration['secure'] ?? null;

        if (! is_string($name) || preg_match('/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/', $name) !== 1) {
            throw new InvalidArgumentException('The session cookie name is invalid.');
        }
        if (! is_int($lifetime) || $lifetime < 1 || $lifetime > 525600) {
            throw new InvalidArgumentException('The session lifetime is invalid.');
        }
        if (! is_bool($http_only) || ($secure !== null && ! is_bool($secure))) {
            throw new InvalidArgumentException('The session cookie security settings are invalid.');
        }
        if (! is_string($same_site) || ! in_array($same_site, ['Lax', 'Strict', 'None'], true)) {
            throw new InvalidArgumentException('The session SameSite setting is invalid.');
        }

        if ($save_path !== null) {
            if (! is_string($save_path) || $save_path === '' || str_contains($save_path, "\0")) {
                throw new InvalidArgumentException('The session storage path is invalid.');
            }

            $save_path = str_starts_with($save_path, '/') ? $save_path : base_path($save_path);
            if (! is_dir($save_path) && ! mkdir($save_path, 0700, true) && ! is_dir($save_path)) {
                throw new RuntimeException('The session storage directory could not be created.');
            }
            if (! is_writable($save_path)) {
                throw new RuntimeException('The session storage directory is not writable.');
            }
            $runtime_root = realpath(base_path('runtime'));
            $resolved_path = realpath($save_path);
            if (
                ! is_string($runtime_root)
                || ! is_string($resolved_path)
                || ! str_starts_with($resolved_path, $runtime_root.DIRECTORY_SEPARATOR)
            ) {
                throw new InvalidArgumentException('Explicit session paths must remain under the application runtime directory.');
            }
        }
        if (session_name($name) === '') {
            throw new RuntimeException('The session cookie name could not be configured.');
        }

        $is_secure = $secure ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off');
        $options = [
            'gc_maxlifetime' => $lifetime * 60,
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'use_trans_sid' => false,
            'cookie_httponly' => $http_only,
            'cookie_secure' => $is_secure,
            'cookie_samesite' => $same_site,
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
        ];
        if (is_string($save_path)) {
            $options['save_path'] = $save_path;
        }
        $started = session_start($options);
        if (! $started) {
            throw new RuntimeException('The session could not be started.');
        }
        self::ageFlashData();
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::assertKey($key);
        self::start();
        $_SESSION[$key] = $value;
        self::removeFlashReference($key);
    }

    /** Stores a key reserved for a Webkernel security subsystem. */
    public static function putReserved(string $key, mixed $value): void
    {
        if (! str_starts_with($key, '_webkernel_') || str_contains($key, "\0")) {
            throw new InvalidArgumentException('Reserved session keys must use the Webkernel prefix.');
        }
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function flash(string $key, mixed $value): void
    {
        self::assertKey($key);
        self::start();
        $_SESSION[$key] = $value;
        $flash = $_SESSION[self::FLASH_KEY] ?? ['new' => [], 'old' => []];
        $flash['new'] = array_values(array_unique([...($flash['new'] ?? []), $key]));
        $flash['old'] = array_values(array_diff($flash['old'] ?? [], [$key]));
        $_SESSION[self::FLASH_KEY] = $flash;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        self::removeFlashReference($key);
        return $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
        self::removeFlashReference($key);
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        self::start();
        $values = $_SESSION;
        unset($values[self::FLASH_KEY]);
        return $values;
    }

    public static function regenerate(bool $delete_old = true): void
    {
        self::start();
        if (! session_regenerate_id($delete_old)) {
            throw new RuntimeException('The session identifier could not be rotated.');
        }
    }

    public static function invalidate(): void
    {
        self::start();
        $_SESSION = [];
        if (! session_regenerate_id(true)) {
            throw new RuntimeException('The session could not be invalidated.');
        }
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (! session_destroy()) {
            throw new RuntimeException('The session could not be destroyed.');
        }
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);
        $_SESSION = [];
    }

    public static function save(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && ! session_write_close()) {
            throw new RuntimeException('The session data could not be saved.');
        }
    }

    private static function ageFlashData(): void
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? ['new' => [], 'old' => []];
        if (! is_array($flash)) {
            unset($_SESSION[self::FLASH_KEY]);
            return;
        }
        foreach (is_array($flash['old'] ?? null) ? $flash['old'] : [] as $key) {
            if (is_string($key)) {
                unset($_SESSION[$key]);
            }
        }
        $new = is_array($flash['new'] ?? null) ? $flash['new'] : [];
        $_SESSION[self::FLASH_KEY] = ['new' => [], 'old' => $new];
    }

    private static function removeFlashReference(string $key): void
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        if (! is_array($flash)) {
            return;
        }
        $flash['new'] = array_values(array_diff(is_array($flash['new'] ?? null) ? $flash['new'] : [], [$key]));
        $flash['old'] = array_values(array_diff(is_array($flash['old'] ?? null) ? $flash['old'] : [], [$key]));
        $_SESSION[self::FLASH_KEY] = $flash;
    }

    private static function assertKey(string $key): void
    {
        if ($key === '' || str_starts_with($key, '_webkernel_') || str_contains($key, "\0")) {
            throw new InvalidArgumentException('The session key is invalid or reserved.');
        }
    }
}
