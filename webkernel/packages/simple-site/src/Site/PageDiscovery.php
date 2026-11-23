<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Site;

use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class PageDiscovery
{
    /** @return list<array{path: string, view: string, props: array<string, mixed>, file: string}> */
    public static function pages(array $site): array
    {
        $root = $site['pages'] ?? null;
        if (! is_string($root) || ! is_dir($root)) {
            throw new RuntimeException('A simple site must configure an existing pages directory.');
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && str_ends_with($entry->getFilename(), '.view.php')) {
                $files[] = $entry->getPathname();
            }
        }
        sort($files, SORT_NATURAL);
        $pages = [];
        foreach ($files as $file) {
            $relative = substr($file, strlen(rtrim($root, '/\\')) + 1);
            $viewPath = substr($relative, 0, -strlen('.view.php'));
            $parts = array_map(self::stripOrderPrefix(...), explode('/', str_replace('\\', '/', $viewPath)));
            if (end($parts) === 'index') {
                array_pop($parts);
            }
            $urlPath = implode('/', array_filter($parts, static fn (string $part): bool => $part !== ''));
            $key = trim($urlPath, '/');
            if (isset($pages[$key])) {
                throw new RuntimeException(sprintf('Duplicate simple-site URL "%s" from %s and %s.', $urlPath, $pages[$key]['file'], $file));
            }

            $props = self::pageProps($file);
            $viewName = rtrim((string) ($site['view_namespace'] ?? ''), ':');
            if ($viewName !== '') {
                $viewName .= '::';
            }
            $viewPrefix = trim((string) ($site['view_prefix'] ?? 'pages'), '.');
            if ($viewPrefix !== '') {
                $viewName .= $viewPrefix.'.';
            }
            $viewName .= str_replace('/', '.', substr($relative, 0, -strlen('.view.php')));
            $pages[$key] = [
                'path' => $urlPath,
                'view' => $viewName,
                'props' => $props,
                'file' => $file,
            ];
        }
        ksort($pages, SORT_NATURAL);
        if (! isset($pages[''])) {
            throw new RuntimeException('A simple site requires a root index.view.php page.');
        }

        return array_values($pages);
    }

    /** @return array{path: string, view: string, props: array<string, mixed>, file: string}|null */
    public static function find(array $site, string $requestedPath): ?array
    {
        $requestedPath = trim(rawurldecode($requestedPath), '/');
        if (str_contains($requestedPath, "\0") || str_contains($requestedPath, '\\')
            || ($requestedPath !== '' && preg_match('#(?:^|/)\.\.?($|/)#', $requestedPath))
        ) {
            return null;
        }

        $root = $site['pages'] ?? null;
        if (! is_string($root) || ! is_dir($root)) {
            throw new RuntimeException('A simple site must configure an existing pages directory.');
        }
        $file = rtrim($root, '/\\').'/'.($requestedPath === '' ? 'index.view.php' : $requestedPath.'.view.php');
        if (! is_file($file) && $requestedPath !== '') {
            $file = rtrim($root, '/\\').'/'.$requestedPath.'/index.view.php';
        }
        if (is_file($file)) {
            $relative = substr($file, strlen(rtrim($root, '/\\')) + 1);
            $viewPath = substr($relative, 0, -strlen('.view.php'));
            $props = self::pageProps($file);
            $viewName = rtrim((string) ($site['view_namespace'] ?? ''), ':');
            if ($viewName !== '') {
                $viewName .= '::';
            }
            $viewPrefix = trim((string) ($site['view_prefix'] ?? 'pages'), '.');
            if ($viewPrefix !== '') {
                $viewName .= $viewPrefix.'.';
            }
            $viewName .= str_replace('/', '.', $viewPath);

            return ['path' => $requestedPath, 'view' => $viewName, 'props' => $props, 'file' => $file];
        }

        // Keep the full lookup for URLs generated from order-prefixed directories.
        foreach (self::pages($site) as $page) {
            if ($page['path'] === $requestedPath) {
                return $page;
            }
        }

        return null;
    }

    /** @return array{file: string, mime_type: string}|null */
    public static function publicAsset(array $site, string $requestedPath): ?array
    {
        $root = $site['pages'] ?? null;
        if (! is_string($root) || ! is_dir($root)) {
            return null;
        }

        $relativePath = rawurldecode(trim($requestedPath, '/'));
        if ($relativePath === '' || str_contains($relativePath, "\0") || str_contains($relativePath, '\\')
            || preg_match('#(?:^|/)\.\.?($|/)#', $relativePath)
        ) {
            return null;
        }

        $mimeTypes = [
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            'css' => 'text/css; charset=UTF-8',
            'eot' => 'application/vnd.ms-fontobject',
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'm4v' => 'video/x-m4v',
            'mov' => 'video/quicktime',
            'mp4' => 'video/mp4',
            'js' => 'text/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'ogv' => 'video/ogg',
            'ogg' => 'video/ogg',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'ttf' => 'font/ttf',
            'webp' => 'image/webp',
            'webm' => 'video/webm',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        $mimeType = $mimeTypes[strtolower(pathinfo($relativePath, PATHINFO_EXTENSION))] ?? null;
        if (! is_string($mimeType)) {
            return null;
        }

        $resolvedRoot = realpath($root);
        $resolvedFile = is_string($resolvedRoot) ? realpath($resolvedRoot.'/'.$relativePath) : false;
        if (! is_string($resolvedRoot) || ! is_string($resolvedFile)
            || ! str_starts_with($resolvedFile, rtrim($resolvedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            || ! is_file($resolvedFile) || ! is_readable($resolvedFile)
        ) {
            return null;
        }

        return ['file' => $resolvedFile, 'mime_type' => $mimeType];
    }

    private static function stripOrderPrefix(string $part): string
    {
        return preg_replace('/\A\d{1,4}[-_]/', '', $part) ?? $part;
    }

    /** Read the static SEO/feed metadata declared directly on a page component. */
    private static function pageProps(string $file): array
    {
        $template = file_get_contents($file);
        if (! is_string($template)
            || preg_match('/<x-[a-z0-9_.-]+::page\b([^>]*)>/is', $template, $component) !== 1
        ) {
            return [];
        }

        $props = [];
        preg_match_all('/(?<![:\w-])(title|description|robots|rss|status|updated_at|canonical)\s*=\s*(["\'])(.*?)\2/is', $component[1], $attributes, PREG_SET_ORDER);
        foreach ($attributes as $attribute) {
            $name = $attribute[1];
            $value = html_entity_decode($attribute[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $props[$name] = match ($name) {
                'rss' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $value,
                'status' => ctype_digit($value) ? (int) $value : $value,
                default => $value,
            };
        }

        return $props;
    }
}
