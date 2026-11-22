<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Imagery;

use Composer\InstalledVersions;
use InvalidArgumentException;

/**
 * SVG icons from res/icons/{set}/{name}.svg
 */
final class Icon
{
    /** @var array<string, string>|null */
    private static ?array $dumped = null;

    /** @var array<string, string> */
    private static array $loaded = [];

    /** @return array{title: array{en: string, fr: string}, description: array{en: string, fr: string}} */
    public static function metadata(): array
    {
        return [
            'title' => ['en' => 'Icon', 'fr' => 'Icône'],
            'description' => [
                'en' => 'Renders a self-hosted icon with accessible sizing.',
                'fr' => 'Affiche une icône locale avec une taille accessible.',
            ],
        ];
    }

    /** @return array{title: array{en: string, fr: string}, description: array{en: string, fr: string}, data: array<string, mixed>, code: string} */
    public static function demo(): array
    {
        return [
            ...self::metadata(),
            'data' => ['icon' => 'check', 'set' => 'lucide', 'width' => 20, 'height' => 20],
            'code' => '<x-webkernel::icon icon="check" set="lucide" width="20" height="20" />',
        ];
    }

    /**
     * @return list<string>
     */
    public static function sets(): array
    {
        $sets = config('icons.sets', []);

        return is_array($sets) ? array_keys($sets) : ['lucide', 'simple-icons', 'custom'];
    }

    public static function package_root(): string
    {
        $root = \dirname(__DIR__);

        return \realpath($root) ?: $root;
    }

    public static function path(string $name, string $set = 'lucide'): string
    {
        if (preg_match('/\A[a-zA-Z0-9_-]+\z/', $name) !== 1 || preg_match('/\A[a-zA-Z0-9_-]+\z/', $set) !== 1) {
            throw new InvalidArgumentException('Icon set and name must contain only letters, numbers, underscores or hyphens.');
        }
        $set_config = config('icons.sets.'.$set);
        $configured_path = is_array($set_config) ? ($set_config['path'] ?? null) : null;
        if (is_string($configured_path) && str_starts_with($configured_path, '@imagery/')) {
            $package_path = InstalledVersions::getInstallPath('webkernel/imagery');
            if (! is_string($package_path)) {
                throw new InvalidArgumentException('The imagery package path is unavailable.');
            }
            $root = realpath($package_path);
            if (! is_string($root)) {
                throw new InvalidArgumentException('The imagery package path is invalid.');
            }
            $directory = $root.'/'.ltrim(substr($configured_path, strlen('@imagery/')), '/\\');
        } elseif (is_string($configured_path)) {
            $directory = base_path(trim($configured_path, '/\\'));
        } else {
            $directory = self::package_root().'/res/icons/'.$set;
        }

        return $directory.'/'.$name.'.svg';
    }

    /** Renders one configured icon with bounded dimensions and escaped attributes. */
    /** @param array<string, mixed> $attributes */
    public static function render(
        string $name,
        string $class = '',
        string $style = '',
        string $set = 'lucide',
        ?int $width = null,
        ?int $height = null,
        array $attributes = [],
    ): string {
        $svg = self::svg($name, $set);
        if ($svg === '' || preg_match('/<svg\\b[^>]*>/s', $svg, $matches) !== 1) {
            return '';
        }
        $set_config = config('icons.sets.'.$set, []);
        $set_config = is_array($set_config) ? $set_config : [];
        $width = min(max(1, $width ?? (int) ($set_config['width'] ?? 16)), max(1, (int) config('icons.limits.width', 48)));
        $height = min(max(1, $height ?? (int) ($set_config['height'] ?? 16)), max(1, (int) config('icons.limits.height', 48)));
        $class = trim((string) ($set_config['class'] ?? '').' '.$class);
        $root = preg_replace("~\\s+(?:class|width|height|style)\\s*=\\s*(?:\"[^\"]*\"|'[^']*')~i", '', $matches[0]);
        if (! is_string($root)) {
            return '';
        }
        $values = ['class' => $class, 'width' => (string) $width, 'height' => (string) $height];
        if ($style !== '') {
            $values['style'] = $style;
        }
        foreach ($attributes as $key => $value) {
            if (
                ! is_string($key)
                || preg_match('/\\A[a-zA-Z_:][a-zA-Z0-9_.:-]*\\z/', $key) !== 1
                || str_starts_with(strtolower($key), 'on')
                || in_array(strtolower($key), ['class', 'width', 'height'], true)
                || $value === null
                || $value === false
            ) {
                continue;
            }
            $values[str_replace('_', '-', $key)] = $value === true ? true : (string) $value;
        }
        $injected = '';
        foreach ($values as $key => $value) {
            if ($value === true) {
                $injected .= ' '.htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            } else {
                $injected .= ' '.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"';
            }
        }

        return substr_replace($svg, $injected, strlen($root) - 1, 0);
    }

    public static function svg(string $name, string $set = 'lucide'): string
    {
        $key = $set.'/'.$name;
        if (isset(self::$loaded[$key])) {
            return self::$loaded[$key];
        }
        $dumped = self::dumped();
        if (isset($dumped[$key]) && \is_string($dumped[$key]) && $dumped[$key] !== '') {
            return self::$loaded[$key] = $dumped[$key];
        }
        $file = self::path($name, $set);
        if (\is_file($file)) {
            $svg = \file_get_contents($file);

            return self::$loaded[$key] = \is_string($svg) ? $svg : '';
        }
        foreach (self::sets() as $fallback) {
            if ($fallback === $set) {
                continue;
            }
            $file = self::path($name, $fallback);
            if (\is_file($file)) {
                $svg = \file_get_contents($file);

                return self::$loaded[$key] = \is_string($svg) ? $svg : '';
            }
        }

        return self::$loaded[$key] = '';
    }

    public static function flush(): void
    {
        self::$dumped = null;
        self::$loaded = [];
    }

    /**
     * @return array<string, string>
     */
    private static function dumped(): array
    {
        if (self::$dumped !== null) {
            return self::$dumped;
        }
        $file = \function_exists('base_path') ? \base_path('runtime/cache/declarations/webkernel_icons.php') : '';
        if ($file === '' || ! \is_file($file)) {
            return self::$dumped = [];
        }
        $data = require $file;

        return self::$dumped = \is_array($data) ? $data : [];
    }
}
