<?php declare(strict_types=1);

namespace App\Studio\Catalog;

/** Ranks published catalogue offerings for the optional services discovery selector. */
final class MarketFitRecommender
{
    /** @return list<array{id:string,name:array<string,string>|string,label:string,href:string,type:string,score:int}> */
    public static function recommend(string $industry, string $businessNeed, array $journey = [], int $limit = 5): array
    {
        $ranked = [];
        foreach (OfferingCatalog::all() as $offering) {
            if (($offering['active'] ?? false) !== true || ($offering['visibility'] ?? 'catalog') !== 'catalog') continue;
            $type = (string) ($offering['type'] ?? '');
            if (!in_array($type, ['mandate', 'software', 'playbook', 'service'], true)) continue;

            $needs = is_array($offering['business_needs'] ?? null) ? $offering['business_needs'] : [];
            $industries = is_array($offering['industries'] ?? null) ? $offering['industries'] : [];
            $score = 0;
            if ($industry !== '' && in_array($industry, $industries, true)) $score += 2;
            if ($businessNeed !== '' && in_array($businessNeed, $needs, true)) $score += 2;
            $priceMad = self::oneTimePriceMad($offering);

            if (($journey['team_size'] ?? '') === 'solo' && self::hasAny($needs, ['sales-marketing', 'revenue-growth'])) $score++;
            if (($journey['team_size'] ?? '') === '2-10' && in_array('operations', $needs, true)) $score++;
            if (($journey['team_size'] ?? '') === '11-50' && self::hasAny($needs, ['organization', 'operations'])) $score++;
            if (($journey['team_size'] ?? '') === '51+' && self::hasAny($needs, ['transformation', 'data-analytics'])) $score++;

            if (($journey['annual_revenue'] ?? '') === 'under-1m' && $priceMad !== null && $priceMad <= 50000) $score++;
            if (($journey['annual_revenue'] ?? '') === '1m-10m' && $priceMad !== null && $priceMad <= 150000) $score++;
            if (($journey['annual_revenue'] ?? '') === '10m-50m' && $priceMad !== null && $priceMad >= 50000) $score++;
            if (($journey['annual_revenue'] ?? '') === '50m+' && $priceMad !== null && $priceMad >= 150000) $score++;

            if (in_array($journey['profit_margin'] ?? '', ['negative', '0-10'], true) && in_array('cost-reduction', $needs, true)) $score += 2;
            if (in_array($journey['profit_margin'] ?? '', ['negative', '0-10'], true) && in_array('operations', $needs, true)) $score++;
            if (($journey['profit_margin'] ?? '') === '26+' && self::hasAny($needs, ['revenue-growth', 'market-expansion'])) $score++;
            if (($journey['ownership'] ?? '') === 'solo' && in_array('revenue-growth', $needs, true)) $score++;
            if (($journey['ownership'] ?? '') === 'partners' && self::hasAny($needs, ['organization', 'transformation'])) $score++;

            if ($score < 1) continue;
            $id = (string) ($offering['id'] ?? '');
            if ($id === '') continue;
            $nameMap = $offering['name'] ?? null;
            try {
                $label = function_exists('__t') ? __t(is_array($nameMap) || is_string($nameMap) ? $nameMap : null) : '';
            } catch (\Throwable) {
                $label = '';
            }
            if ($label === '') $label = is_string($nameMap) ? $nameMap : (string) ($nameMap['en'] ?? $id);
            $name = is_array($nameMap) || is_string($nameMap) ? $nameMap : ['en' => $label];
            $servicesPath = static fn (string $path): string => '/services'.($path !== '' ? '/'.$path : '');
            $href = match ($type) {
                'software' => $servicesPath('').'#modules',
                'playbook' => $servicesPath('').'#playbooks',
                'service' => $id === 'sales-force' ? $servicesPath('').'#revenue-force' : $servicesPath(''),
                default => $servicesPath($id),
            };
            $ranked[] = ['id' => $id, 'name' => $name, 'label' => $label, 'href' => $href, 'type' => $type, 'score' => $score];
        }

        usort($ranked, static fn (array $left, array $right): int => ($right['score'] <=> $left['score']) ?: strcmp($left['label'], $right['label']));
        $limit = max(1, min(20, $limit));
        $representatives = [];
        $selectedIds = [];
        $selectedTypes = [];
        foreach ($ranked as $item) {
            if (isset($selectedTypes[$item['type']])) continue;
            $representatives[] = $item;
            $selectedIds[$item['id']] = true;
            $selectedTypes[$item['type']] = true;
        }
        $remaining = [];
        foreach ($ranked as $item) {
            if (!isset($selectedIds[$item['id']])) $remaining[] = $item;
        }
        $selected = array_merge($representatives, $remaining);
        return array_slice($selected, 0, $limit);
    }

    private static function oneTimePriceMad(array $offering): ?float
    {
        foreach (($offering['prices'] ?? []) as $component) {
            if (!is_array($component) || ($component['billing'] ?? '') !== 'one_time') continue;
            $amount = $component['amount_mad'] ?? null;
            if (is_numeric($amount)) return (float) $amount;
        }
        return null;
    }

    /** @param list<string> $values @param list<string> $expected */
    private static function hasAny(array $values, array $expected): bool
    {
        return array_intersect($expected, $values) !== [];
    }
}
