<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Getters;

use InvalidArgumentException;
use RuntimeException;

/** Reads Webkernel's compiled runtime inventory of component templates by namespace. */
final class GetWebkernelComponentCatalog
{
    public const int PAGE_SIZE = 12;

    /** @var array<string, list<string>>|null */
    private static ?array $catalog = null;

    /** @var array{version: string, count: int, page_count: int, page_size: int}|null */
    private static ?array $summary = null;

    /** @var array<int, array<string, list<string>>> */
    private static array $pages = [];

    private function __construct() {}

    /** @return array<string, list<string>> */
    public static function byNamespace(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $manifest_path = base_path('runtime/cache/declarations/webkernel_component_catalog.php');
        if (! is_file($manifest_path)) {
            throw new RuntimeException('The Webkernel component catalog is missing; run composer dump-autoload.');
        }
        $catalog = require $manifest_path;
        if (! is_array($catalog)) {
            throw new RuntimeException('The Webkernel component catalog has an invalid format.');
        }

        return self::$catalog = $catalog;
    }

    public static function count(): int
    {
        return self::summary()['count'];
    }

    public static function pageCount(): int
    {
        return self::summary()['page_count'];
    }

    /** @return array<string, list<string>> Names grouped by component namespace for this page. */
    public static function page(int $page): array
    {
        if ($page < 1 || $page > self::pageCount()) {
            throw new InvalidArgumentException('Component catalog page is out of range.');
        }
        if (isset(self::$pages[$page])) {
            return self::$pages[$page];
        }
        if (self::count() === 0) {
            return self::$pages[$page] = [];
        }

        $summary = self::summary();
        $manifest_path = base_path(sprintf(
            'runtime/cache/declarations/webkernel_component_catalog_%s_page_%d.php',
            $summary['version'],
            $page,
        ));
        if (! is_file($manifest_path)) {
            throw new RuntimeException('The compiled component catalog page is missing; run composer dump-autoload.');
        }
        $components = require $manifest_path;
        if (! is_array($components)) {
            throw new RuntimeException('The compiled component catalog page has an invalid format.');
        }

        return self::$pages[$page] = $components;
    }

    /** @return array{version: string, count: int, page_count: int, page_size: int} */
    private static function summary(): array
    {
        if (self::$summary !== null) {
            return self::$summary;
        }

        $manifest_path = base_path('runtime/cache/declarations/webkernel_component_catalog_summary.php');
        if (! is_file($manifest_path)) {
            throw new RuntimeException('The component catalog summary is missing; run composer dump-autoload.');
        }
        $summary = require $manifest_path;
        if (
            ! is_array($summary)
            || ! is_string($summary['version'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', $summary['version']) !== 1
            || ! is_int($summary['count'] ?? null)
            || ! is_int($summary['page_count'] ?? null)
            || ! is_int($summary['page_size'] ?? null)
            || $summary['count'] < 0
            || $summary['page_count'] < 1
            || $summary['page_size'] !== self::PAGE_SIZE
        ) {
            throw new RuntimeException('The component catalog summary has an invalid format.');
        }

        return self::$summary = $summary;
    }
}
