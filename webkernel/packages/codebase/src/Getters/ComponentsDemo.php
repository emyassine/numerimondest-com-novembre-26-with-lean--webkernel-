<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Getters;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\View\AttributeBag;

/** Selects component-owned metadata and demo data for the component gallery. */
final class ComponentsDemo
{
    /** @var array<string, class-string> */
    private static array $component_classes = [];

    /** @var array<string, array{title: array{en: string, fr: string}, description: array{en: string, fr: string}, preview?: bool, preview_note?: array{en: string, fr: string}}> */
    private static array $component_metadata = [];

    /** @return array{title: string, description: string, preview: bool, preview_note: string} */
    public static function describe(string $namespace, string $name): array
    {
        $metadata = self::component_metadata($namespace, $name);

        return [
            'title' => __t($metadata['title']),
            'description' => __t($metadata['description']),
            'preview' => $metadata['preview'] ?? true,
            'preview_note' => __t($metadata['preview_note'] ?? ['en' => '', 'fr' => '']),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $demos = [];
        foreach (GetWebkernelComponentCatalog::byNamespace() as $namespace => $names) {
            foreach ($names as $name) {
                $demos[$namespace.'::'.$name] = self::select($namespace, $name);
            }
        }

        return $demos;
    }

    /** @return array{title: string, description: string, data: array<string, mixed>, code: string, preview: bool, preview_note: string} */
    public static function select(string $namespace, string $name): array
    {
        $component_class = self::component_class($namespace, $name);
        $metadata = self::component_metadata($namespace, $name);
        $brand_mark_url = self::brand_mark_url($namespace, $name);
        $demo = in_array($component_class, [
            'Webkernel\\Components\\Image',
            'Webkernel\\Components\\ImageEntry',
            'Webkernel\\Components\\UserMenu',
        ], true)
            ? $component_class::demo($brand_mark_url)
            : $component_class::demo();

        if (! is_array($demo['data'] ?? null) || ! is_string($demo['code'] ?? null)) {
            throw new RuntimeException(sprintf('Component "%s::%s" has an invalid demo declaration.', $namespace, $name));
        }

        return [
            'title' => __t($metadata['title']),
            'description' => __t($metadata['description']),
            'data' => array_replace([
                'attributes' => new AttributeBag(),
                'slot' => '',
            ], $demo['data']),
            'code' => $demo['code'],
            'preview' => $metadata['preview'] ?? true,
            'preview_note' => __t($metadata['preview_note'] ?? ['en' => '', 'fr' => '']),
        ];
    }

    /** @return array{title: array{en: string, fr: string}, description: array{en: string, fr: string}, preview?: bool, preview_note?: array{en: string, fr: string}} */
    private static function component_metadata(string $namespace, string $name): array
    {
        $key = $namespace.'::'.$name;
        if (isset(self::$component_metadata[$key])) {
            return self::$component_metadata[$key];
        }

        $component_class = self::component_class($namespace, $name);
        if (! is_callable([$component_class, 'metadata'])) {
            throw new RuntimeException(sprintf('Component "%s::%s" has no metadata declaration.', $namespace, $name));
        }

        $metadata = $component_class::metadata();
        if (! is_array($metadata['title'] ?? null) || ! is_array($metadata['description'] ?? null)) {
            throw new RuntimeException(sprintf('Component "%s::%s" has invalid metadata.', $namespace, $name));
        }

        return self::$component_metadata[$key] = $metadata;
    }

    /** @return class-string */
    private static function component_class(string $namespace, string $name): string
    {
        $key = $namespace.'::'.$name;
        if (isset(self::$component_classes[$key])) {
            return self::$component_classes[$key];
        }

        $component_namespaces = [
            'actions' => 'Webkernel\\Actions',
            'forms' => 'Webkernel\\Forms',
            'imagery' => 'Webkernel\\Imagery',
            'notifications' => 'Webkernel\\Notifications',
            'schemas' => 'Webkernel\\Schemas',
            'tables' => 'Webkernel\\Tables',
            'webkernel' => 'Webkernel\\Components',
            'widgets' => 'Webkernel\\Widgets',
        ];
        $component_namespace = $component_namespaces[$namespace] ?? null;
        if ($component_namespace === null) {
            throw new InvalidArgumentException(sprintf('Unknown component namespace "%s".', $namespace));
        }

        $component_class_name = match ($namespace.'::'.$name) {
            'webkernel::carousel-item' => 'CarouselItem',
            'webkernel::list' => 'ListComponent',
            'webkernel::switch' => 'SwitchField',
            'webkernel::qr-code' => 'QRCode',
            default => str_replace(' ', '', ucwords(str_replace(['-', '.'], ' ', $name))),
        };
        $component_class = $component_namespace.'\\'.$component_class_name;
        if (! class_exists($component_class)) {
            throw new RuntimeException(sprintf('Component class "%s" is not autoloadable.', $component_class));
        }

        return self::$component_classes[$key] = $component_class;
    }

    private static function brand_mark_url(string $namespace, string $name): string
    {
        if (! in_array($namespace.'::'.$name, [
            'webkernel::image-entry',
            'webkernel::image',
            'webkernel::user-menu',
        ], true)) {
            return '';
        }

        return GetAsset::url('webkernel/webkernel', 'app/resources/assets/branding/favicon-64.png');
    }
}
