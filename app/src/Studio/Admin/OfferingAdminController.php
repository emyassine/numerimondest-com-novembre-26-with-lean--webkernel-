<?php declare(strict_types=1);

namespace App\Studio\Admin;

use App\Studio\Persistence\OfferingStore;
use App\Studio\Catalog\OfferingType;
use App\Studio\Catalog\OfferingRelationType;
use App\Studio\Catalog\Taxonomy\Industry;
use App\Studio\Catalog\Taxonomy\BusinessNeed;
use Webkernel\Route\Request;
use Webkernel\HttpFoundation\Response;
use Webkernel\View\View;

/** Bearer-token protected catalogue editor and publication boundary. */
final class OfferingAdminController
{
    public static function index(Request $request, ?OfferingStore $store = null): Response
    {
        if (!self::authorized($request)) return self::unauthorized();
        $store ??= new OfferingStore();
        $draft = $store->current('draft');
        $published = $store->current('published');
        $source = $draft ?? $published ?? ['revision' => null, 'catalogue' => ['offerings' => []]];
        $json = json_encode($source['catalogue'], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $value = $json;
        $revision = htmlspecialchars((string) ($source['revision'] ?? 'none'), ENT_QUOTES, 'UTF-8');
        $history = $store->history();
        $historyHtml = '';
        foreach ($history as $entry) {
            $historyHtml .= '<li>Revision '.(int) $entry['revision'].' · '.htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8').'</li>';
        }
        $html = View::make('studio::pages.offerings-admin', [
            'revision' => $revision,
            'catalogueJson' => $value,
            'historyHtml' => $historyHtml,
        ])->render();
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    public static function saveDraft(Request $request, ?OfferingStore $store = null): Response
    {
        if (!self::authorized($request)) return self::unauthorized();
        $data = json_decode($request->content(), true);
        if (!is_array($data) || !isset($data['offerings']) || !is_array($data['offerings'])) return new Response('Invalid catalogue payload', 422);
        try { self::validate($data['offerings']); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        try { ($store ?? new OfferingStore())->saveDraft($data, 'admin'); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        return new Response('Draft saved', 204);
    }

    public static function preview(Request $request): Response
    {
        if (!self::authorized($request)) return self::unauthorized();
        $data = json_decode($request->content(), true);
        if (!is_array($data) || !is_array($data['offerings'] ?? null)) return new Response('Invalid catalogue payload.', 422);
        try { self::validate($data['offerings']); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        $preview = [];
        foreach ($data['offerings'] as $offering) {
            $preview[] = [
                'id' => $offering['id'],
                'name' => $offering['name']['en'] ?? reset($offering['name']) ?: $offering['id'],
                'type' => $offering['type'],
                'active' => $offering['active'],
                'visibility' => $offering['visibility'] ?? 'catalog',
                'prices' => array_map(static fn (array $price): array => array_intersect_key($price, array_flip(['id', 'amount_mad', 'minimum_mad', 'maximum_mad', 'percentage', 'billing', 'basis'])), array_filter($offering['prices'], 'is_array')),
                'relations' => count($offering['relations']),
            ];
        }
        return new Response(json_encode(['valid' => true, 'offering_count' => count($preview), 'offerings' => $preview], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']);
    }

    public static function publish(Request $request, ?OfferingStore $store = null): Response
    {
        if (!self::authorized($request)) return self::unauthorized();
        try { $revision = ($store ?? new OfferingStore())->publish('admin'); }
        catch (\Throwable $error) { return new Response($error->getMessage(), 409); }
        return new Response(json_encode(['revision' => $revision], JSON_THROW_ON_ERROR), 200, ['Content-Type' => 'application/json']);
    }

    private static function authorized(Request $request): bool
    {
        if (PHP_SAPI !== 'cli' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === '' || $_SERVER['HTTPS'] === 'off')) return false;
        $secret = getenv('NM_OFFERINGS_ADMIN_TOKEN');
        $authorization = $request->header('authorization');
        if (!is_string($secret) || strlen($secret) < 32 || !is_string($authorization)) return false;
        if (str_starts_with($authorization, 'Bearer ')) return hash_equals('Bearer '.$secret, $authorization);
        if (str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6), true);
            return is_string($decoded) && hash_equals('admin:'.$secret, $decoded);
        }
        return false;
    }

    private static function unauthorized(): Response
    {
        return new Response('Authentication required.', 401, ['WWW-Authenticate' => 'Basic realm="Numerimondes offerings administration", charset="UTF-8"', 'Cache-Control' => 'no-store']);
    }

    /** @param list<array<string,mixed>> $offerings */
    private static function validate(array $offerings): void
    {
        $ids = [];
        foreach ($offerings as $row) {
            if (!is_array($row)) throw new \InvalidArgumentException('Each offering must be an object.');
            foreach (['id','type','name','description','industries','business_needs','prices','relations','active'] as $field) {
                if (!array_key_exists($field, $row)) throw new \InvalidArgumentException('Missing offering field: '.$field);
            }
            if (!is_string($row['id']) || isset($ids[$row['id']])) throw new \InvalidArgumentException('Offering IDs must be unique strings.');
            $ids[$row['id']] = true;
            if (OfferingType::tryFrom((string) $row['type']) === null) throw new \InvalidArgumentException('Unknown offering type.');
            foreach (['name','description'] as $field) {
                if (!is_array($row[$field]) || $row[$field] === []) throw new \InvalidArgumentException($field.' must contain locale translations.');
                foreach ($row[$field] as $locale => $text) if (!is_string($locale) || !is_string($text)) throw new \InvalidArgumentException('Translations must map locales to strings.');
            }
            if (!is_array($row['industries']) || !is_array($row['business_needs']) || !is_array($row['prices']) || !is_array($row['relations']) || !is_bool($row['active'])) throw new \InvalidArgumentException('Classifications, prices, relations, or active flag have invalid types.');
            foreach ($row['industries'] as $industry) if (!is_string($industry) || Industry::tryFrom($industry) === null) throw new \InvalidArgumentException('Unknown industry: '.(string) $industry);
            foreach ($row['business_needs'] as $need) if (!is_string($need) || BusinessNeed::tryFrom($need) === null) throw new \InvalidArgumentException('Unknown business need: '.(string) $need);
            foreach ($row['prices'] as $price) {
                if (!is_array($price) || !is_string($price['id'] ?? null) || $price['id'] === '') throw new \InvalidArgumentException('Each price component needs an identifier.');
                if (!in_array($price['billing'] ?? null, ['one_time', 'monthly', 'quarterly', 'annual', 'percentage'], true)) throw new \InvalidArgumentException('Unknown price billing interval.');
                foreach (['amount_mad', 'minimum_mad', 'maximum_mad'] as $amount) {
                    if (isset($price[$amount]) && (!is_numeric($price[$amount]) || !is_finite((float) $price[$amount]) || (float) $price[$amount] < 0)) throw new \InvalidArgumentException('MAD price values must be finite and non-negative.');
                }
                if (isset($price['percentage']) && (!is_numeric($price['percentage']) || (float) $price['percentage'] <= 0 || (float) $price['percentage'] > 100 || !is_array($price['basis'] ?? null))) throw new \InvalidArgumentException('Percentage components require a valid percentage and calculation basis.');
                if (isset($price['minimum_mad'], $price['maximum_mad']) && $price['minimum_mad'] > $price['maximum_mad']) throw new \InvalidArgumentException('Price minimum exceeds maximum.');
                if (isset($price['unit']) && (!is_string($price['unit']) || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $price['unit'])
                    || !is_numeric($price['amount_mad'] ?? null) || isset($price['minimum_mad']) || isset($price['maximum_mad'])
                    || is_numeric($price['percentage'] ?? null))) throw new \InvalidArgumentException('Unit prices require a fixed amount and a valid unit identifier.');
            }
            foreach ($row['relations'] as $relation) {
                if (!is_array($relation) || !is_string($relation['offering_id'] ?? null) || !is_string($relation['type'] ?? null)
                    || OfferingRelationType::tryFrom($relation['type']) === null
                    || !in_array($relation['visibility'] ?? 'detail_only', ['catalog', 'detail_only', 'hidden'], true)
                    || !is_bool($relation['optional'] ?? true)) throw new \InvalidArgumentException('Invalid offering relationship.');
                $targets[$row['id']][] = $relation['offering_id'];
            }
        }
        foreach ($targets ?? [] as $parent => $children) foreach ($children as $child) {
            if (!isset($ids[$child]) || $parent === $child) throw new \InvalidArgumentException('Offering relationship points to an unknown or identical offering.');
        }
    }
}
