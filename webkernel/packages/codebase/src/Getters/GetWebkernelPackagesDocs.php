<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Getters;

use Composer\InstalledVersions;
use RuntimeException;
use Webkernel\I18n\Catalog;
use Webkernel\I18n\I18nContext;
use Webkernel\View\Markdown;

/** Collects Markdown documentation shipped by installed Webkernel packages. */
final class GetWebkernelPackagesDocs
{
    private const int APCU_TTL = 5;

    /** @var list<array<string, mixed>>|null */
    private static ?array $packages_docs = null;

    /** @var array<string, list<array<string, mixed>>> */
    private static array $localized = [];

    private function __construct() {}

    /**
     * @return string Installed framework documentation release.
     */
    public static function version(): string
    {
        return ltrim((string) InstalledVersions::getPrettyVersion('webkernel/codebase'), 'v');
    }

    /**
     * Selects one translation per topic, preserving its actual content language.
     *
     * @param $locale string|null
     *
     * @return list<array<string, mixed>>
     */
    public static function get(?string $locale = null): array
    {
        $locale ??= I18nContext::get_locale() ?: Catalog::default_locale();
        if (isset(self::$localized[$locale])) {
            return self::$localized[$locale];
        }
        $fallback = Catalog::default_locale();
        $packages = [];
        foreach (self::sources() as $package) {
            $translations = [];
            foreach ($package['docs'] as $doc) {
                $translations[$doc['slug']][$doc['locale']] = $doc;
            }
            $package['docs'] = [];
            $groups = [];
            foreach ($translations as $variants) {
                $doc = $variants[$locale] ?? $variants[$fallback] ?? reset($variants);
                $doc['locales'] = array_keys($variants);
                $doc['source_paths'] = array_column($variants, 'path');
                $doc['is_fallback'] = $doc['locale'] !== $locale;
                $package['docs'][] = $doc;
                $groups[$doc['group']][] = $doc;
            }
            $package['groups'] = $groups;
            $packages[] = $package;
        }

        return self::$localized[$locale] = $packages;
    }

    /** @return list<array<string, mixed>> */
    private static function sources(): array
    {
        if (self::$packages_docs !== null) {
            return self::$packages_docs;
        }
        $package_names = array_values(array_filter(
            InstalledVersions::getInstalledPackages(),
            static fn (string $package_name): bool => str_starts_with($package_name, 'webkernel/'),
        ));
        sort($package_names, SORT_STRING);
        $cache_key = self::cacheKey($package_names).':'.Catalog::default_locale();
        if (self::apcuEnabled()) {
            $cached = apcu_fetch($cache_key, $cache_hit);
            if ($cache_hit && is_array($cached)) {
                return self::$packages_docs = $cached;
            }
        }
        $packages = [];
        foreach ($package_names as $package_name) {
            $short_name = substr($package_name, strlen('webkernel/'));
            try {
                $directory = webkernel_package($short_name, 'docs');
            } catch (RuntimeException) {
                continue;
            }
            if (! is_dir($directory)) {
                continue;
            }
            $docs = [];
            foreach (self::markdownFiles($directory) as $file) {
                $content = file_get_contents($file);
                if (! is_string($content)) {
                    continue;
                }
                $path = substr($file, strlen($directory) + 1);
                $locale = preg_match('/\.([a-z]{2}(?:[-_][a-z]{2})?)\.md$/i', $path, $match) === 1
                    ? I18nContext::normalize($match[1])
                    : Catalog::default_locale();
                $logical_path = preg_replace('/(?:\.[a-z]{2}(?:[-_][a-z]{2})?)?\.md$/i', '', $path) ?? $path;
                $segments = array_map(
                    static fn (string $part): string => preg_replace('/^\d+[-_]/', '', $part) ?? $part,
                    explode('/', $logical_path),
                );
                if (end($segments) === 'index') {
                    $segments[array_key_last($segments)] = 'overview';
                }
                $slug = implode('/', array_map(self::slug(...), $segments));
                $docs[] = [
                    'title' => self::title($content, $logical_path),
                    'path' => $path,
                    'slug' => $slug,
                    'group' => count($segments) > 1 ? $segments[0] : 'overview',
                    'anchor' => 'doc-'.$short_name.'-'.str_replace('/', '-', $slug),
                    'locale' => $locale,
                    'content' => $content,
                ];
            }
            if ($docs === []) {
                continue;
            }
            $packages[] = [
                'name' => $short_name,
                'title' => ucwords(str_replace('-', ' ', $short_name)),
                'slug' => self::slug($short_name),
                'version' => (string) InstalledVersions::getPrettyVersion($package_name),
                'anchor' => 'package-'.$short_name,
                'docs' => $docs,
            ];
        }
        if (self::apcuEnabled()) {
            apcu_store($cache_key, $packages, self::APCU_TTL);
        }

        return self::$packages_docs = $packages;
    }

    /**
     * Resolves one document, its local links, and its heading navigation.
     *
     * @param $package_slug string
     * @param $document_slug string
     * @param $base_url string
     * @param $locale string|null
     *
     * @return array<string, mixed>|null
     */
    public static function document(string $package_slug, string $document_slug, string $base_url, ?string $locale = null): ?array
    {
        foreach (self::get($locale) as $package) {
            if ($package['slug'] !== $package_slug) {
                continue;
            }
            $paths = [];
            foreach ($package['docs'] as $doc) {
                foreach ($doc['source_paths'] as $path) {
                    $paths[strtolower($path)] = $base_url.'/'.$package_slug.'/'.$doc['slug'];
                }
            }
            foreach ($package['docs'] as $index => $doc) {
                if ($doc['slug'] !== $document_slug) {
                    continue;
                }
                $content = self::resolveDocumentationLinks($doc['content'], $doc['path'], $paths, $base_url.'/'.$package_slug);
                $html = Markdown::render($content);
                $html = preg_replace('/\A\s*<h1\b[^>]*>.*?<\/h1>\s*/s', '', $html, 1) ?? $html;
                $sections = [];
                $ids = [];
                $html = preg_replace_callback(
                    '/<h([2-6]) class="markdown-heading">(.*?)<\/h\1>/s',
                    static function (array $match) use (&$sections, &$ids): string {
                        $title = html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $id = self::slug($title);
                        $duplicate = $ids[$id] ?? 0;
                        $ids[$id] = $duplicate + 1;
                        $id .= $duplicate === 0 ? '' : '-'.$duplicate;
                        if ((int) $match[1] <= 3) {
                            $sections[] = ['id' => $id, 'title' => $title, 'level' => (int) $match[1]];
                        }

                        return '<h'.$match[1].' class="markdown-heading" id="'.$id.'">'.$match[2].'</h'.$match[1].'>';
                    },
                    $html,
                ) ?? $html;

                return [
                    'package' => $package,
                    'document' => $doc,
                    'html' => $html,
                    'sections' => $sections,
                    'previous' => $package['docs'][$index - 1] ?? null,
                    'next' => $package['docs'][$index + 1] ?? null,
                ];
            }

            return null;
        }

        return null;
    }

    /** @param list<string> $package_names */
    private static function cacheKey(array $package_names): string
    {
        $versions = [];
        foreach ($package_names as $package_name) {
            $versions[] = $package_name.':'.(InstalledVersions::getReference($package_name)
                ?? InstalledVersions::getPrettyVersion($package_name)
                ?? 'installed');
        }

        return 'webkernel:package-docs:v3:'.hash('xxh3', implode('|', $versions));
    }

    /** @return list<string> */
    private static function markdownFiles(string $directory): array
    {
        $directories = [$directory];
        $files = [];
        while ($current_directory = array_pop($directories)) {
            foreach (glob($current_directory.'/*.md', GLOB_NOSORT) ?: [] as $file) {
                if (is_file($file)) {
                    $files[] = $file;
                }
            }
            foreach (glob($current_directory.'/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [] as $child_directory) {
                if (! is_link($child_directory)) {
                    $directories[] = $child_directory;
                }
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private static function title(string $content, string $fallback): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $match) === 1) {
            return trim($match[1]);
        }

        return pathinfo($fallback, PATHINFO_FILENAME);
    }

    /**
     * @param $content string
     * @param $source_path string
     * @param $docs_by_path array<string, string>
     * @param $fallback_url string
     *
     * @return string
     */
    private static function resolveDocumentationLinks(
        string $content,
        string $source_path,
        array $docs_by_path,
        string $fallback_url,
    ): string {
        $resolved = preg_replace_callback(
            '/(```[^\n]*\n.*?```|`[^`\n]*`)(*SKIP)(*F)|\]\(([^)\s]+\.md(?:#[^)]+)?)\)/si',
            static function (array $match) use ($source_path, $docs_by_path, $fallback_url): string {
                [$target_path, $fragment] = array_pad(explode('#', $match[2], 2), 2, '');
                if (preg_match('/\A(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $target_path) === 1) {
                    return $match[0];
                }
                $segments = [];
                foreach (explode('/', dirname($source_path).'/'.$target_path) as $segment) {
                    if ($segment === '' || $segment === '.') {
                        continue;
                    }
                    if ($segment === '..') {
                        array_pop($segments);
                        continue;
                    }
                    $segments[] = $segment;
                }
                $relative_target = strtolower(implode('/', $segments));
                $url = $docs_by_path[$relative_target] ?? $fallback_url;

                return ']('.$url.($fragment !== '' ? '#'.$fragment : '').')';
            },
            $content,
        );

        return is_string($resolved) ? $resolved : $content;
    }

    private static function slug(string $value): string
    {
        $slug_source = preg_replace('/\.md$/i', '', $value);
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $slug_source), '-'));

        return $slug !== '' ? $slug : 'documentation';
    }

    private static function apcuEnabled(): bool
    {
        return function_exists('apcu_fetch')
            && function_exists('apcu_store')
            && (! function_exists('apcu_enabled') || apcu_enabled());
    }
}
