<?php declare(strict_types=1);

namespace Webkernel\Commands;

use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\Route\Route;

/** Lists application and Composer-compiled panel routes. */
final class RouteListCommand
{
    #[ConsoleCommand(name: 'route:list', description: 'List application and panel routes')]
    public function __invoke(): int
    {
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
}
