<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Site;

use RuntimeException;
use Webkernel\Config\Config;

final class SiteRegistry
{
    /** @return array<string, mixed> */
    public static function get(string $prefix): array
    {
        $sites = Config::get('simple-site.sites', []);
        if (! is_array($sites)) {
            throw new RuntimeException('The simple-site.sites configuration must be an array.');
        }
        foreach ($sites as $site) {
            if (is_array($site) && is_string($site['prefix'] ?? null) && rtrim($site['prefix'], '/') === rtrim($prefix, '/')) {
                return $site;
            }
        }
        throw new RuntimeException('The requested simple site is not configured.');
    }

    /** @return array<string, mixed> */
    public static function forCurrentRequest(): array
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($uri) ? $uri : '/';
        $sites = Config::get('simple-site.sites', []);
        if (is_array($sites)) {
            foreach ($sites as $site) {
                $prefix = is_array($site) ? ($site['prefix'] ?? null) : null;
                if (is_string($prefix) && ($path === rtrim($prefix, '/') || str_starts_with($path, rtrim($prefix, '/').'/'))) {
                    return $site;
                }
            }
        }
        throw new RuntimeException('No simple site matches the current request.');
    }
}
