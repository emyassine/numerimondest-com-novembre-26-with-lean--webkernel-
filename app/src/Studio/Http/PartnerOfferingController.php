<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Partners\PartnerAccess;
use App\Studio\Partners\PartnerSettlementPolicy;
use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;
use App\Studio\Catalog\Offering;
use App\Studio\Pricing\CurrencyRates;
use App\Studio\Pricing\OfferingPriceResolver;
use App\Studio\Pricing\PartnerPriceRule;
use App\Studio\Pricing\QuoteSnapshot;
use Webkernel\HttpFoundation\JsonResponse;
use Webkernel\HttpFoundation\Response;
use Webkernel\Route\Request;

final class PartnerOfferingController
{
    /** Returns one authorized catalogue offering without exposing the partner's entire catalogue. */
    public static function show(Request $request, string $id, ?OfferingStore $store = null): Response|JsonResponse
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $id)) return new JsonResponse(['error' => 'not_found'], 404);
        $listingRequest = new Request('GET', '/api/v1/offerings', headers: array_filter([
            'authorization' => $request->header('authorization'),
            'origin' => $request->header('origin'),
        ], 'is_string'));
        $listing = self::index($listingRequest, $store);
        if ($listing->getStatusCode() !== 200) return $listing;
        $payload = json_decode($listing->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($payload['offerings'] ?? [] as $offering) {
            if (($offering['id'] ?? null) !== $id) continue;
            $itemPayload = [
                'schema_version' => $payload['schema_version'],
                'catalog_revision' => $payload['catalog_revision'],
                'offering' => $offering,
            ];
            $etag = '"'.hash('sha256', json_encode([$id, $itemPayload], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)).'"';
            $headers = ['ETag' => $etag, 'Cache-Control' => 'private, max-age=300', 'Vary' => 'Authorization, Origin'];
            if (trim((string) $request->header('if-none-match')) === $etag) return new Response('', 304, $headers);
            return new JsonResponse($itemPayload, 200, $headers);
        }

        // Related detail-only services can be fetched by an authorized partner only
        // when an allowed catalogue parent explicitly exposes the relationship.
        $published = ($store ?? new OfferingStore())->current('published');
        $partner = PartnerAccess::authenticate($request->header('authorization'), $request->header('origin'));
        if ($published !== null && $partner !== null && in_array($id, $partner['offering_ids'] ?? [], true)) {
            $records = [];
            foreach ($published['catalogue']['offerings'] ?? [] as $record) {
                if (is_array($record) && is_string($record['id'] ?? null)) $records[$record['id']] = $record;
            }
            foreach ($records as $parent) {
                if (($parent['active'] ?? false) !== true || ($parent['visibility'] ?? 'catalog') !== 'catalog'
                    || !in_array($parent['id'] ?? null, $partner['offering_ids'] ?? [], true)) continue;
                foreach ($parent['relations'] ?? [] as $relation) {
                    if (!is_array($relation) || ($relation['offering_id'] ?? null) !== $id
                        || !in_array($relation['type'] ?? null, ['complementary', 'maintenance', 'follow_up'], true)
                        || ($relation['visibility'] ?? '') !== 'detail_only') continue;
                    $record = $records[$id] ?? null;
                    if (!is_array($record) || ($record['active'] ?? false) !== true || ($record['visibility'] ?? '') !== 'detail-only') break;
                    $ruleData = $partner['pricing_rules'][$id] ?? null;
                    if (is_array($ruleData)) {
                        $resolved = (new OfferingPriceResolver())->resolve(Offering::fromArray($record), 'MAD', PartnerPriceRule::fromArray($partner['id'], $id, $ruleData));
                        foreach ($resolved as $index => $price) if (isset($record['prices'][$index])) $record['prices'][$index]['amount_mad'] = $price['amount'];
                    }
                    $public = array_intersect_key($record, array_flip(['id', 'type', 'name', 'description', 'summary', 'industries', 'business_needs', 'active', 'visibility', 'category', 'practice', 'format', 'engagement', 'status', 'deliverables', 'terms']));
                    $public['prices'] = array_map(static fn (array $price): array => array_intersect_key($price, array_flip(['id', 'amount_mad', 'minimum_mad', 'maximum_mad', 'billing', 'percentage', 'basis', 'label', 'unit'])), array_filter($record['prices'] ?? [], 'is_array'));
                    $public['parent'] = ['id' => $parent['id'], 'relation' => array_intersect_key($relation, array_flip(['type', 'optional', 'visibility']))];
                    $itemPayload = ['schema_version' => $payload['schema_version'], 'catalog_revision' => $published['revision'], 'offering' => $public];
                    $etag = '"'.hash('sha256', json_encode([$partner['id'], $itemPayload], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)).'"';
                    $headers = ['ETag' => $etag, 'Cache-Control' => 'private, max-age=300', 'Vary' => 'Authorization, Origin'];
                    if (trim((string) $request->header('if-none-match')) === $etag) return new Response('', 304, $headers);
                    return new JsonResponse($itemPayload, 200, $headers);
                }
            }
        }
        return new JsonResponse(['error' => 'not_found'], 404);
    }

    public static function index(Request $request, ?OfferingStore $store = null): Response|JsonResponse
    {
        $partner = PartnerAccess::authenticate($request->header('authorization'), $request->header('origin'));
        if ($partner === null) return new JsonResponse(['error' => 'unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);

        $published = ($store ?? new OfferingStore())->current('published');
        if ($published === null) return new JsonResponse(['error' => 'catalogue_unavailable'], 503);
        $catalogue = $published['catalogue'];
        $allById = [];
        foreach ($catalogue['offerings'] ?? [] as $record) if (is_array($record) && is_string($record['id'] ?? null)) $allById[$record['id']] = $record;
        $allowedIds = array_fill_keys(array_filter($partner['offering_ids'] ?? [], 'is_string'), true);
        $offerings = [];
        foreach ($allById as $offering) {
            if (!is_array($offering) || !isset($offering['id']) || !isset($allowedIds[$offering['id']])) continue;
            if (($offering['active'] ?? true) !== true || ($offering['visibility'] ?? 'catalog') === 'detail-only') continue;
            $ruleData = $partner['pricing_rules'][$offering['id']] ?? null;
            if (is_array($ruleData) && is_array($offering['prices'] ?? null)) {
                $rule = PartnerPriceRule::fromArray($partner['id'], $offering['id'], $ruleData);
                $model = Offering::fromArray($offering);
                $resolved = (new OfferingPriceResolver())->resolve($model, 'MAD', $rule);
                foreach ($resolved as $index => $price) {
                    if (isset($offering['prices'][$index])) $offering['prices'][$index]['amount_mad'] = $price['amount'];
                }
            }
            $public = array_intersect_key($offering, array_flip([
                'id', 'type', 'name', 'description', 'summary', 'industries', 'business_needs', 'active', 'visibility',
            ]));
            $public['prices'] = array_map(static fn (array $price): array => array_intersect_key($price, array_flip([
                'id', 'amount_mad', 'minimum_mad', 'maximum_mad', 'billing', 'percentage', 'basis', 'label', 'unit',
            ])), array_filter($offering['prices'] ?? [], 'is_array'));
            $visibleRelations = array_filter($offering['relations'] ?? [], static fn ($relation): bool => is_array($relation) && (!isset($relation['offering_id']) || (is_string($relation['offering_id']) && isset($allowedIds[$relation['offering_id']]))));
            $public['relations'] = array_map(static fn (array $relation): array => array_intersect_key($relation, array_flip([
                'offering_id', 'type', 'optional', 'visibility',
            ])), array_filter($visibleRelations, 'is_array'));
            // Detail-only extensions are available only inside the explicitly allowed parent.
            $public['extensions'] = [];
            foreach ($offering['relations'] ?? [] as $relation) {
                if (!is_array($relation) || ($relation['type'] ?? null) !== 'extension' || !is_string($relation['offering_id'] ?? null)) continue;
                $extension = $allById[$relation['offering_id']] ?? null;
                if (!is_array($extension) || !isset($allowedIds[$extension['id']]) || ($extension['active'] ?? false) !== true || ($extension['visibility'] ?? null) !== 'detail-only') continue;
                $extensionRuleData = $partner['pricing_rules'][$extension['id']] ?? null;
                if (is_array($extensionRuleData)) {
                    $extensionRule = PartnerPriceRule::fromArray($partner['id'], $extension['id'], $extensionRuleData);
                    $resolved = (new OfferingPriceResolver())->resolve(Offering::fromArray($extension), 'MAD', $extensionRule);
                    foreach ($resolved as $index => $price) if (isset($extension['prices'][$index])) $extension['prices'][$index]['amount_mad'] = $price['amount'];
                }
                $nested = array_intersect_key($extension, array_flip(['id', 'type', 'name', 'description', 'industries', 'business_needs', 'active', 'visibility']));
                $nested['prices'] = array_map(static fn (array $price): array => array_intersect_key($price, array_flip(['id', 'amount_mad', 'minimum_mad', 'maximum_mad', 'billing', 'percentage', 'basis', 'label', 'unit'])), array_filter($extension['prices'] ?? [], 'is_array'));
                $nested['relation'] = array_intersect_key($relation, array_flip(['type', 'optional', 'visibility']));
                $public['extensions'][] = $nested;
            }
            $public['attributes'] = array_intersect_key($offering, array_flip([
                'category', 'practice', 'format', 'engagement', 'status', 'deliverables', 'terms', 'flagship', 'sort_order',
            ]));
            $offerings[] = $public;
        }
        $payload = ['schema_version' => 1, 'catalog_revision' => $published['revision'], 'offerings' => $offerings];
        $etag = '"'.hash('sha256', json_encode([$partner['id'], $payload], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)).'"';
        $headers = ['ETag' => $etag, 'Cache-Control' => 'private, max-age=300', 'Vary' => 'Authorization, Origin'];
        if (trim((string) $request->header('if-none-match')) === $etag) return new Response('', 304, $headers);
        return new JsonResponse($payload, 200, $headers);
    }

    /** Creates an authoritative partner quote; only customer-facing totals are returned. */
    public static function quote(Request $request, ?OfferingStore $store = null, ?QuoteStore $quotes = null): Response|JsonResponse
    {
        $partner = PartnerAccess::authenticate($request->header('authorization'), $request->header('origin'));
        if ($partner === null) return new JsonResponse(['error' => 'unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);
        $input = $request->all();
        if (($input['partner_id'] ?? null) !== $partner['id']) return new JsonResponse(['error' => 'partner_mismatch'], 403);
        if (array_diff(array_keys($input), ['partner_id', 'items', 'currency', 'basis_values', 'basis_currency']) !== []) {
            return new JsonResponse(['error' => 'invalid_request'], 422);
        }
        $items = $input['items'] ?? null;
        $currency = strtoupper((string) ($input['currency'] ?? 'MAD'));
        if (!is_array($items) || $items === [] || count($items) > 30) {
            return new JsonResponse(['error' => 'invalid_items'], 422);
        }
        $ids = [];
        $quantities = [];
        foreach ($items as $item) {
            if (!is_array($item) || array_diff(array_keys($item), ['offering_id', 'quantity']) !== []) return new JsonResponse(['error' => 'invalid_items'], 422);
            $id = $item['offering_id'] ?? null;
            $quantity = $item['quantity'] ?? null;
            if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $id)
                || !is_int($quantity) || $quantity < 1 || $quantity > 1000 || isset($quantities[$id])) {
                return new JsonResponse(['error' => 'invalid_items'], 422);
            }
            $ids[] = $id;
            $quantities[$id] = $quantity;
        }
        $published = ($store ?? new OfferingStore())->current('published');
        if ($published === null) return new JsonResponse(['error' => 'catalogue_unavailable'], 503);
        $rows = [];
        foreach ($published['catalogue']['offerings'] ?? [] as $row) if (is_array($row) && is_string($row['id'] ?? null)) $rows[$row['id']] = $row;
        $allowed = array_fill_keys($partner['offering_ids'] ?? [], true);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $rateData = CurrencyRates::forPricing();
        $basisValues = is_array($input['basis_values'] ?? null) ? $input['basis_values'] : [];
        $basisCurrencies = [];
        if (isset($basisValues['monthly_salary'])) {
            $salaryCurrency = strtoupper((string) ($input['basis_currency'] ?? 'MAD'));
            $basisCurrencies['monthly_salary'] = $salaryCurrency;
            if (!is_numeric($basisValues['monthly_salary']) || !is_finite((float) $basisValues['monthly_salary']) || (float) $basisValues['monthly_salary'] <= 0 || (float) $basisValues['monthly_salary'] > 1000000000) return new JsonResponse(['error' => 'invalid_basis'], 422);
        }
        $lines = [];
        $settlements = [];
        foreach ($ids as $id) {
            $quantity = $quantities[$id];
            $row = $rows[$id] ?? null;
            if (!is_array($row) || !isset($allowed[$id]) || ($row['active'] ?? false) !== true) return new JsonResponse(['error' => 'offering_unavailable', 'offering_id' => $id], 422);
            if (($row['visibility'] ?? 'catalog') === 'detail-only') {
                $parent = null;
                $parentRelation = null;
                foreach ($rows as $candidate) {
                    if (!isset($allowed[$candidate['id'] ?? '']) || !in_array($candidate['id'] ?? null, $ids, true)) continue;
                    foreach ($candidate['relations'] ?? [] as $relation) {
                        if (is_array($relation) && ($relation['offering_id'] ?? null) === $id
                            && in_array($relation['type'] ?? null, ['extension', 'complementary', 'maintenance', 'follow_up'], true)
                            && in_array($relation['visibility'] ?? 'detail_only', ['detail_only', 'catalog'], true)) {
                            $parent = $candidate;
                            $parentRelation = $relation;
                            break 2;
                        }
                    }
                }
                if (!is_array($parent) || !is_array($parentRelation)) return new JsonResponse(['error' => 'related_offering_requires_parent', 'offering_id' => $id], 422);
            }
            try {
                $ruleData = $partner['pricing_rules'][$id] ?? null;
                $rule = is_array($ruleData) ? PartnerPriceRule::fromArray($partner['id'], $id, $ruleData) : null;
                $resolver = new OfferingPriceResolver();
                $prices = $resolver->resolve(Offering::fromArray($row), $currency, $rule, $now, $basisValues, $basisCurrencies, $rateData['rates'], $quantity);
                $madPrices = $resolver->resolve(Offering::fromArray($row), 'MAD', $rule, $now, $basisValues, $basisCurrencies, $rateData['rates'], $quantity);
            } catch (\InvalidArgumentException $error) {
                if ($quantity > 1 && !array_filter($row['prices'] ?? [], static fn ($price): bool => is_array($price) && is_string($price['unit'] ?? null))) {
                    return new JsonResponse(['error' => 'quantity_not_supported', 'offering_id' => $id], 422);
                }
                return new JsonResponse(['error' => 'invalid_pricing_request'], 422);
            } catch (\Throwable) { return new JsonResponse(['error' => 'invalid_pricing_request'], 422); }
            $publicPrices = array_map(static fn (array $price): array => array_intersect_key($price, array_flip(['id', 'billing', 'currency', 'amount', 'unit_amount', 'quantity', 'unit', 'minimum', 'maximum', 'percentage', 'basis', 'label'])), $prices);
            $units = array_values(array_unique(array_filter(array_column($prices, 'unit'))));
            $line = ['offering_id' => $id, 'name' => $row['name'] ?? [], 'quantity' => $quantity, 'unit' => $units[0] ?? null, 'prices' => $publicPrices];
            if (($row['visibility'] ?? 'catalog') === 'detail-only') {
                foreach ($rows as $candidate) foreach ($candidate['relations'] ?? [] as $relation) {
                    if (is_array($relation) && ($relation['type'] ?? null) === 'extension' && ($relation['offering_id'] ?? null) === $id) {
                        $line['parent_id'] = $candidate['id'];
                        $line['parent_name'] = $candidate['name'] ?? [];
                        break 2;
                    }
                }
            }
            $lines[] = $line;
            $settlementLines = [];
            foreach ($madPrices as $price) {
                if (!is_numeric($price['amount'] ?? null)) continue;
                $split = PartnerSettlementPolicy::resolve($partner, $id, (float) $price['amount']);
                $settlementLines[] = ['price_component_id' => $price['id'], 'billing' => $price['billing'], 'quantity' => $price['quantity'] ?? 1, 'unit' => $price['unit'] ?? null, ...$split];
            }
            if ($settlementLines !== []) $settlements[$id] = $settlementLines;
        }
        $pricingRevision = hash('sha256', json_encode([
            'partner_id' => $partner['id'], 'partner_rules' => $partner['pricing_rules'] ?? [],
            'rates' => $rateData['rates'], 'dates' => $rateData['dates'], 'sources' => $rateData['sources'], 'checked_at' => $rateData['checked_at'],
        ], JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $created = $now;
        $quote = new QuoteSnapshot(bin2hex(random_bytes(16)), $partner['id'], (string) $published['revision'], $pricingRevision, $currency, $lines, $created, $created->modify('+30 days'));
        try { ($quotes ?? new QuoteStore())->save($quote, $settlements); }
        catch (\Throwable) { return new JsonResponse(['error' => 'quote_unavailable'], 503); }
        return new JsonResponse($quote->toArray(), 201, ['Cache-Control' => 'no-store', 'Vary' => 'Authorization, Origin']);
    }
}
