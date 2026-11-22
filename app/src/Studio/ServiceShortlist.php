<?php declare(strict_types=1);

namespace App\Studio;

final class ServiceShortlist
{
    private const int MAX_OFFERS = 30;
    private const int RETENTION_DAYS = 30;

    /** @return list<string> */
    public static function all(): array
    {
        $cookieName = self::cookieName();
        $stored = is_string($_COOKIE[$cookieName] ?? null)
            ? json_decode($_COOKIE[$cookieName], true)
            : [];
        if (! is_array($stored) || $stored === []) {
            return [];
        }

        $catalog = ServiceCatalog::all();
        $slugs = array_values(array_unique(array_filter(
            $stored,
            static fn (mixed $slug): bool => is_string($slug) && isset($catalog[$slug]),
        )));

        return array_slice($slugs, 0, self::MAX_OFFERS);
    }

    /** @param list<string> $slugs */
    public static function save(array $slugs): void
    {
        $catalog = ServiceCatalog::all();
        $slugs = array_values(array_slice(array_unique(array_filter(
            $slugs,
            static fn (mixed $slug): bool => is_string($slug) && isset($catalog[$slug]),
        )), 0, self::MAX_OFFERS));
        $cookieName = self::cookieName();
        if ($slugs === []) {
            setcookie($cookieName, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => self::isSecure(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[$cookieName]);
            return;
        }

        $value = json_encode($slugs, JSON_THROW_ON_ERROR);
        setcookie($cookieName, $value, [
            'expires' => time() + self::RETENTION_DAYS * 86400,
            'path' => '/',
            'secure' => self::isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$cookieName] = $value;
    }

    private static function cookieName(): string
    {
        $name = config('nm-studio.shortlist_cookie', 'nm_studio_service_shortlist');

        return is_string($name) && preg_match('/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/', $name) === 1
            ? $name
            : 'nm_studio_service_shortlist';
    }

    private static function isSecure(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    }
}
