<?php declare(strict_types=1);

namespace App\Studio\Http;

use Numerimondes\Offerings\Catalog\OfferingCatalog;
use App\Studio\Analytics\MarketFitAnalytics;
use Webkernel\HttpFoundation\Response;
use Webkernel\View\View;

final class ServiceMandateController
{
    public static function show(string $slug): Response
    {
        $offerings = OfferingCatalog::all();
        $offering = $offerings[$slug] ?? null;
        if (!is_array($offering) || ($offering['active'] ?? false) !== true) {
            return new Response('Not found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        if (($offering['visibility'] ?? 'catalog') !== 'catalog' && !self::isRoutedRelatedOffering($slug, $offerings)) {
            return new Response('Not found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        MarketFitAnalytics::recordEvent('detail_open');

        $name = self::text($offering['name'] ?? [], $slug);
        $description = self::text($offering['description'] ?? [], '');
        $attributes = $offering;
        $image = $offering;
        $displayCurrency = self::displayCurrency();
        $priceDisplay = offering_price_display($offering, $displayCurrency);

        $sections = [];
        $deliverables = (string)($attributes['deliverables'] ?? '');
        $deliverableItems = array_values(array_filter(array_map('trim', explode('·', $deliverables)), static fn(string $item): bool => $item !== ''));
        if ($deliverableItems !== []) $sections[] = ['title' => 'Deliverables', 'items' => $deliverableItems];
        $terms = (string)($attributes['terms'] ?? 'Fixed fee · 50% at kickoff, 50% on delivery');
        $sections[] = ['title' => 'Engagement terms', 'paragraphs' => [$terms]];

        $extensions = [];
        $related = [];
        foreach (($offering['relations'] ?? []) as $relation) {
            $relatedId = $relation['offering_id'] ?? null;
            if (!is_string($relatedId) || !isset($offerings[$relatedId])) continue;
            $child = $offerings[$relatedId];
            $relationType = $relation['type'] ?? '';
            $relationVisibility = $relation['visibility'] ?? 'detail_only';
            if (($child['active'] ?? true) !== true || $relationVisibility === 'hidden') continue;
            if ($relationType === 'extension' && $relationVisibility === 'detail_only') {
                $child['name'] = self::text($child['name'] ?? [], $relatedId);
                $child['description'] = self::text($child['description'] ?? [], '');
                $child['slug'] = $relatedId;
                $child['parent'] = $slug;
                $child['parent_name'] = $name;
                $child['category'] = 'Extension · '.$name;
                $child['format'] = 'Extension';
                $child['href'] = simple_site('studio', 'services/'.$slug);
                $child['parent_id'] = $slug;
                $childPrice = $child['prices'][0] ?? [];
                $child['price_mad'] = is_numeric($childPrice['amount_mad'] ?? null) ? (float) $childPrice['amount_mad'] : null;
                $child['monthly'] = ($childPrice['billing'] ?? '') === 'monthly';
                $child['price_display'] = offering_price_display($child, $displayCurrency);
                $extensions[] = $child;
            } elseif (in_array($relationType, ['complementary', 'maintenance', 'follow_up'], true) && in_array($relationVisibility, ['catalog', 'detail_only'], true)) {
                $child['slug'] = $child['id'];
                $child['name'] = self::text($child['name'] ?? [], $relatedId);
                $child['category'] = (string)($child['category'] ?? ucfirst(str_replace('_', ' ', $relationType)));
                $child['format'] = (string)($child['format'] ?? 'Related offering');
                $child['status'] = (string)($child['status'] ?? 'Taking enquiries');
                $child['summary'] = self::text($child['summary'] ?? $child['description'] ?? [], '');
                $child['description'] = self::text($child['description'] ?? [], '');
                $child['href'] = simple_site('studio', 'services/'.$relatedId);
                $child['price_display'] = offering_price_display($child, $displayCurrency);
                $child['relation_type'] = $relationType;
                $related[] = $child;
            }
        }

        $service = [
            'id' => $offering['id'], 'slug' => $offering['id'], 'type' => $offering['type'] ?? 'service',
            'name' => $name, 'category' => (string)($attributes['category'] ?? 'Mandate'),
            'status' => 'Taking enquiries', 'scope' => $description, 'introduction' => $description,
            'format' => (string)($attributes['format'] ?? 'Mandate'),
            'engagement' => (string)($attributes['engagement'] ?? 'Project'),
            'summary' => self::text($attributes['summary'] ?? $attributes['description'] ?? [], $description),
            'description' => $description,
            'price_display' => $priceDisplay,
            'sections' => $sections, 'extensions' => $extensions,
            'image_source' => $image['image_source'] ?? '', 'image' => $image['image'] ?? '',
            'image_alt' => $image['image_alt'] ?? $name, 'href' => simple_site('studio', 'services/'.$slug),
            'industries' => $offering['industries'] ?? [], 'business_needs' => $offering['business_needs'] ?? [],
        ];
        $html = View::make('studio::partials.offerings.mandate-detail', ['service' => $service, 'relatedServices' => $related])->render();
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param array<string,string>|string $value */
    private static function text(array|string $value, string $fallback): string
    {
        if (is_string($value)) return $value;
        $locale = function_exists('app') ? (string)(config('app.locale', 'en')) : 'en';
        return $value[$locale] ?? $value['en'] ?? reset($value) ?: $fallback;
    }

    private static function displayCurrency(): string
    {
        $settings = config('nm-studio.currencies', []);
        $options = is_array($settings['options'] ?? null) ? $settings['options'] : [];
        $cookieName = is_string($settings['cookie'] ?? null) ? $settings['cookie'] : 'numerimondes_currency';
        $selected = strtoupper((string)($_COOKIE[$cookieName] ?? $settings['default'] ?? 'MAD'));
        return isset($options[$selected]) ? $selected : 'MAD';
    }

    /** @param array<string,array<string,mixed>> $offerings */
    private static function isRoutedRelatedOffering(string $id, array $offerings): bool
    {
        foreach ($offerings as $parent) {
            if (($parent['active'] ?? false) !== true || ($parent['visibility'] ?? 'catalog') !== 'catalog') continue;
            foreach ($parent['relations'] ?? [] as $relation) {
                if (!is_array($relation) || ($relation['offering_id'] ?? null) !== $id
                    || !in_array($relation['type'] ?? null, ['complementary', 'maintenance', 'follow_up'], true)
                    || ($relation['visibility'] ?? '') !== 'detail_only') continue;
                return true;
            }
        }
        return false;
    }

}
