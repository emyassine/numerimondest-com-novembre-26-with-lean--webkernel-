<?php declare(strict_types=1);

namespace Webkernel\Commands;

use Composer\InstalledVersions;
use Webkernel\Config\Config;
use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\Route\Route;

/** Lists routes declared by the application and its Composer packages. */
final class RouteListCommand
{
    #[ConsoleCommand(name: 'route:list', description: 'List application and package routes')]
    public function __invoke(): int
    {
        $root = base_path();
        $root_site_file = $root.'/config/site.php';
        $root_site = is_file($root_site_file) ? require $root_site_file : [];
        if (! is_array($root_site)) {
            throw new \RuntimeException('config/site.php must return an array.');
        }

        $routes = [];
        $dependencies = [$root_site_file, ...glob($root.'/config/*.php') ?: []];
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            $package_root = InstalledVersions::getInstallPath($package);
            if (! is_string($package_root)) {
                continue;
            }
            $site_file = rtrim($package_root, '/\\').'/config/site.php';
            if (! is_file($site_file)) {
                continue;
            }
            $dependencies[] = $site_file;
            $site = require $site_file;
            if (! is_array($site)) {
                continue;
            }
            $routes = [...$routes, ...self::paths($root, $site['routes'] ?? [])];
            foreach (($site['configs'] ?? []) as $config_file) {
                if (is_string($config_file) && is_file($config_file)) {
                    Config::addDirectory(dirname($config_file));
                    $dependencies[] = $config_file;
                }
            }
        }

        $routes = [...$routes, ...self::paths($root, $root_site['routes'] ?? [])];
        foreach (($root_site['configs'] ?? []) as $config_file) {
            if (is_string($config_file) && is_file($config_file)) {
                Config::addDirectory(dirname($config_file));
                $dependencies[] = $config_file;
            }
        }
        Config::boot($root, vendor_path(), $root.'/runtime/cache');
        Route::useDeclaredRouteFiles(array_values(array_unique($routes)), array_values(array_unique($dependencies)));

        $routes = Route::list();
        if ($routes === []) {
            console()->info('No routes are registered.');
            return 0;
        }
        $rows = [['METHOD', 'URI', 'NAME', 'ACTION']];
        foreach ($routes as $route) {
            $rows[] = [implode('|', $route['methods']), $route['uri'], $route['name'], $route['action']];
        }
        $widths = [];
        foreach ($rows as $row) {
            foreach ($row as $index => $value) { $widths[$index] = max($widths[$index] ?? 0, strlen($value)); }
        }
        foreach ($rows as $index => $row) {
            echo implode('  ', array_map(static fn (string $value, int $column): string => str_pad($value, $widths[$column]), $row, array_keys($row))).PHP_EOL;
            if ($index === 0) { echo implode('  ', array_map(static fn (int $width): string => str_repeat('-', $width), $widths)).PHP_EOL; }
        }
        return 0;
    }

    /** @return list<string> */
    private static function paths(string $root, mixed $paths): array
    {
        if (! is_array($paths)) {
            return [];
        }
        $resolved = [];
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $candidate = str_starts_with($path, '/') ? $path : $root.'/'.$path;
            $resolved[] = realpath($candidate) ?: $candidate;
        }
        return $resolved;
    }
}
