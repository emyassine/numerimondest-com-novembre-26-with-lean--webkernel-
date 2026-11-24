<?php declare(strict_types=1);

namespace App\Studio;

use RuntimeException;

final class ServiceCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        static $services = null;

        if (is_array($services)) {
            return self::withCurrentPrices($services);
        }

        $files = glob(base_path('app/resources/data/studio/services/*.php')) ?: [];
        sort($files, SORT_NATURAL);
        $services = [];

        foreach ($files as $file) {
            $service = require $file;

            if (! is_array($service) || ! is_string($service['slug'] ?? null)) {
                throw new RuntimeException('Invalid service data in '.$file);
            }

            $source = self::sourceFor($service);
            $service['source'] = $source;
            $service['image_source'] = $service['image'] ?? $source['cover'] ?? $source['card_image'] ?? $source['image'];
            $service['image'] = simple_site('studio', $service['image_source']);
            $service['image_alt'] ??= $source['cover_alt'] ?? $source['card_image_alt'] ?? $service['name'];
            $service['image_width'] = 1200;
            $service['image_height'] = 800;
            $service['href'] = simple_site('studio', 'services/'.$service['slug']);
            $service['status'] ??= $source['status'] ?? 'Taking enquiries';
            $service['class'] = 'marketplace-service--'.$service['slug'];
            $services[$service['slug']] = $service;
        }

        $services = array_replace($services, MandateCatalog::selectable());
        $imageReferences = [
            'brand' => 'brand-kit-complete',
            'digital' => 'website-build',
            'systems' => 'crm-client-portal',
            'intel' => 'ops-audit',
            'recruit' => 'remote-talent-search',
            'sales' => 'revenue-desk-pilot',
        ];
        foreach ($services as &$service) {
            if (($service['type'] ?? null) !== 'mandate') {
                continue;
            }
            $reference = $services[$imageReferences[$service['cat']] ?? ''] ?? [];
            $service['image_source'] = $reference['image_source'] ?? '';
            $service['image'] = $reference['image'] ?? '';
            $service['image_alt'] = $reference['image_alt'] ?? $service['name'];
            $service['image_width'] = $reference['image_width'] ?? 1200;
            $service['image_height'] = $reference['image_height'] ?? 800;
        }
        unset($service);
        return self::withCurrentPrices($services);
    }

    /** @return array<string, mixed> */
    public static function find(string $slug): array
    {
        $service = self::all()[$slug] ?? null;

        if (! is_array($service)) {
            throw new RuntimeException('Service not found: '.$slug);
        }

        return $service;
    }

    /** @param array<string, mixed> $service
     *  @return array<string, mixed>
     */
    private static function sourceFor(array $service): array
    {
        $projectSlug = $service['project_slug'] ?? null;

        if (is_string($projectSlug)) {
            return ProjectCatalog::find($projectSlug);
        }

        throw new RuntimeException('A service must reference a project: '.($service['slug'] ?? 'unknown'));
    }

    /** Apply cookie-dependent prices after loading static service definitions. */
    private static function withCurrentPrices(array $services): array
    {
        foreach ($services as &$service) {
            if (isset($service['price_amount'], $service['price_currency'])) {
                $service['price_display'] = ($service['price_lead'] ?? 'From ')
                    .price((float) $service['price_amount'], (string) $service['price_currency'], null, (string) ($service['price_style'] ?? 'prestige'))
                    .(isset($service['price_interval']) ? ' '.$service['price_interval'] : '')
                    .(isset($service['price_note']) ? ' · '.$service['price_note'] : '');
            } elseif (isset($service['fee_percentage'])) {
                $service['price_display'] = (string) $service['fee_percentage'].'% of the recruited candidate’s monthly salary';
            }
        }
        unset($service);

        return $services;
    }
}
