<?php declare(strict_types=1);

namespace App\Studio\Presentation;

use App\Studio\Catalog\OfferingCatalog;
use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;
use App\Studio\Pricing\CurrencyRates;
use Webkernel\Platform\Support\View\ComponentAttributeBag;

/** Builds the complete public services page view model from the published catalogue. */
final class ServicesPageData
{
    /** @return array<string,mixed> */
    public static function build(array $query, array $cookies): array
    {
        $records = OfferingCatalog::all();
        $currencyConfig = config('nm-studio.currencies', []);
        $currencyOptions = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
        $cookieName = (string) ($currencyConfig['cookie'] ?? 'numerimondes_currency');
        $selectedCurrency = is_string($cookies[$cookieName] ?? null) && isset($currencyOptions[$cookies[$cookieName]])
            ? $cookies[$cookieName]
            : (string) ($currencyConfig['default'] ?? 'MAD');

        $catalog = ['mandates' => [], 'modules' => [], 'playbooks' => []];
        $all = [];
        foreach ($records as $id => $record) {
            if (($record['active'] ?? false) !== true) continue;
            $type = $record['type'] ?? '';
            if ($type === 'software') {
                $record['scope'] = __t($record['description'] ?? []);
                $record['name'] = __t($record['name'] ?? []);
                $record['price_mad'] = null;
                $record['price_display'] = '';
                $record['onboarding_price_display'] = '';
                foreach (offering_price_components($record, $selectedCurrency) as $component) {
                    $componentRecord = $record;
                    $componentRecord['prices'] = self::componentPrices($record, $component);
                    $display = offering_price_display($componentRecord, $selectedCurrency);
                    if (str_ends_with((string) ($component['id'] ?? ''), '-onboarding')) {
                        $record['onboarding_price_display'] = $display;
                    } elseif (($component['billing'] ?? '') === 'monthly' && $record['price_display'] === '') {
                        $record['price_mad'] = $component['amount'] ?? $component['minimum'] ?? null;
                        $record['price_display'] = $display;
                    }
                }
                $catalog['modules'][] = $record;
                continue;
            }
            if ($type === 'playbook') {
                $record['name'] = __t($record['name'] ?? []);
                $record['gateway'] = '';
                foreach ($record['relations'] ?? [] as $relation) {
                    if (!is_array($relation) || ($relation['type'] ?? null) !== 'gateway') continue;
                    $gateway = $records[$relation['offering_id'] ?? ''] ?? null;
                    if (is_array($gateway) && ($gateway['active'] ?? false) === true) {
                        $record['gateway'] = __t($gateway['name'] ?? []);
                        break;
                    }
                }
                $record['price_mad'] = $record['prices'][0]['amount_mad'] ?? null;
                $record['price_display'] = offering_price_display($record, $selectedCurrency);
                $catalog['playbooks'][] = $record;
                continue;
            }
            if ($type !== 'mandate' || ($record['visibility'] ?? 'catalog') !== 'catalog') continue;

            $practice = (string) ($record['practice'] ?? 'digital');
            $mandate = $record;
            $mandate['name'] = __t($record['name'] ?? []);
            $mandate['scope'] = __t($record['description'] ?? []);
            $mandate['slug'] = $id;
            $mandate['engagement'] = (string) ($record['engagement'] ?? 'Project');
            $mandate['format'] = (string) ($record['format'] ?? 'Mandate');
            $mandate['summary'] = __t($record['summary'] ?? $record['description'] ?? []);
            $mandate['price_mad'] = $record['prices'][0]['amount_mad'] ?? $record['prices'][0]['minimum_mad'] ?? null;
            $mandate['price_display'] = offering_price_display($record, $selectedCurrency);
            $mandate['status'] = $record['status'] ?? 'Taking enquiries';
            $mandate['extensions'] = [];
            foreach (($record['relations'] ?? []) as $relation) {
                if (!is_array($relation) || ($relation['type'] ?? '') !== 'extension' || ($relation['visibility'] ?? '') !== 'detail_only') continue;
                $extensionId = $relation['offering_id'] ?? null;
                $extension = is_string($extensionId) ? ($records[$extensionId] ?? null) : null;
                if (!is_array($extension) || ($extension['visibility'] ?? '') !== 'detail_only' || ($extension['active'] ?? false) !== true) continue;
                $extension['name'] = __t($extension['name'] ?? []);
                $extension['description'] = __t($extension['description'] ?? []);
                $extension['slug'] = $extension['id'];
                $extension['href'] = simple_site('studio', 'services/'.$id);
                $extension['price_display'] = offering_price_display($extension, $selectedCurrency);
                $extension['price_mad'] = $extension['prices'][0]['amount_mad'] ?? $extension['prices'][0]['minimum_mad'] ?? null;
                $extension['monthly'] = ($extension['prices'][0]['billing'] ?? '') === 'monthly';
                $mandate['extensions'][] = $extension;
            }
            $mandate['image_source'] = $record['image_source'] ?? '';
            $mandate['image'] = $record['image'] ?? '';
            $mandate['image_alt'] = __t($record['image_alt'] ?? []) ?: $mandate['name'];
            $mandate['image_width'] = $record['image_width'] ?? 1200;
            $mandate['image_height'] = $record['image_height'] ?? 800;
            $mandate['href'] = simple_site('studio', 'services/'.$id);
            $mandate['class'] = 'marketplace-service--'.$id;
            $mandate['catalog_mandate'] = true;
            $mandate['practice'] = $practice;
            $mandate['practice_name'] = [
                'brand' => 'Brand', 'digital' => 'Digital', 'systems' => 'Systems',
                'intel' => 'Advisory & research', 'recruit' => 'Talent', 'sales' => 'Sales',
            ][$practice] ?? ucfirst($practice);
            $catalog['mandates'][] = $mandate;
            $all[$id] = $mandate;
        }

        $revenueForce = $records['sales-force'] ?? [];
        if ($revenueForce !== []) {
            $revenueForce['name'] = __t($revenueForce['name'] ?? []);
            $revenueForce['slug'] = $revenueForce['id'];
            $revenueForce['price_components'] = offering_price_components($revenueForce, $selectedCurrency);
            $revenueForce['setup_price'] = $revenueForce['monthly_price'] = $revenueForce['recruitment_price'] = null;
            foreach ($revenueForce['price_components'] as $index => $component) {
                $componentRecord = $revenueForce;
                $componentRecord['prices'] = self::componentPrices($revenueForce, $component);
                $component['display'] = offering_price_display($componentRecord, $selectedCurrency);
                $revenueForce['price_components'][$index] = $component;
                if (($component['billing'] ?? '') === 'one_time') $revenueForce['setup_price'] = $component;
                elseif (($component['billing'] ?? '') === 'monthly') $revenueForce['monthly_price'] = $component;
                elseif (($component['billing'] ?? '') === 'percentage') $revenueForce['recruitment_price'] = $component;
            }
        }

        $slugify = static fn (string $value): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        $practiceMap = $engagementMap = [];
        foreach ($all as $service) {
            $practiceMap[$slugify((string) $service['practice_name'])] = $service['practice_name'];
            $engagementName = $service['engagement'] ?? 'Project';
            $engagementMap[$slugify((string) $engagementName)] = $engagementName;
        }
        asort($practiceMap, SORT_NATURAL | SORT_FLAG_CASE);
        asort($engagementMap, SORT_NATURAL | SORT_FLAG_CASE);
        $requestedPractice = is_string($query['practice'] ?? null) ? $query['practice'] : '';
        $requestedEngagement = is_string($query['engagement'] ?? null) ? $query['engagement'] : '';
        $servicesByPractice = [];
        foreach ($all as $service) $servicesByPractice[$slugify((string) $service['practice_name'])][] = $service;

        return [
            'allServices' => $all, 'mandateCount' => count($all) + ($revenueForce !== [] ? 1 : 0), 'services' => $all, 'servicesByPractice' => $servicesByPractice,
            'catalogData' => $catalog, 'revenueForce' => $revenueForce,
            'industryOptions' => Industry::options(), 'businessNeedOptions' => BusinessNeed::options(),
            'currencyOptions' => $currencyOptions,
            'displayCurrency' => $selectedCurrency, 'rateMetadata' => CurrencyRates::displayMetadata($selectedCurrency),
            'searchQuery' => is_string($query['q'] ?? null) ? trim($query['q']) : '',
            'ownerOnly' => ($query['owner'] ?? null) === '1', 'practiceMap' => $practiceMap, 'engagementMap' => $engagementMap,
            'selectedPractice' => array_key_exists($requestedPractice, $practiceMap) ? $requestedPractice : '',
            'selectedEngagement' => array_key_exists($requestedEngagement, $engagementMap) ? $requestedEngagement : '',
            'threeColumnGrid' => (new ComponentAttributeBag())->grid(['default' => 1, 'md' => 2, 'lg' => 3])->style('gap: 1rem'),
            'fourColumnGrid' => (new ComponentAttributeBag())->grid(['default' => 1, 'md' => 2, 'lg' => 4])->style('gap: 1rem'),
        ];
    }

    /** @return list<array<string,mixed>> */
    private static function componentPrices(array $offering, array $component): array
    {
        return array_values(array_filter($offering['prices'] ?? [], static fn (array $price): bool => ($price['id'] ?? null) === ($component['id'] ?? null)));
    }
}
