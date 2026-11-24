<?php declare(strict_types=1);

namespace App\Studio\Admin;

use App\Studio\Persistence\OfferingStore;
use App\Studio\Catalog\OfferingType;
use App\Studio\Catalog\Taxonomy\Industry;
use App\Studio\Catalog\Taxonomy\BusinessNeed;
use Webkernel\HttpFoundation\Request;
use Webkernel\HttpFoundation\Response;

/** Minimal JSON administration boundary; routes must apply Webkernel CSRF to writes. */
final class OfferingAdminController
{
    public static function index(Request $request): Response
    {
        if (!self::authorized($request)) return new Response('Unauthorized', 401);
        $store = new OfferingStore();
        $draft = $store->current('draft');
        $published = $store->current('published');
        return new Response(json_encode(['draft' => $draft, 'published' => $published], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function saveDraft(Request $request): Response
    {
        if (!self::authorized($request)) return new Response('Unauthorized', 401);
        $data = json_decode((string) $request->getContent(), true);
        if (!is_array($data) || !isset($data['offerings']) || !is_array($data['offerings'])) return new Response('Invalid catalogue payload', 422);
        try { self::validate($data['offerings']); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        try { (new OfferingStore())->saveDraft($data, 'admin'); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        return new Response('Draft saved', 204);
    }

    public static function publish(Request $request): Response
    {
        if (!self::authorized($request)) return new Response('Unauthorized', 401);
        try { $revision = (new OfferingStore())->publish('admin'); }
        catch (\Throwable $error) { return new Response($error->getMessage(), 409); }
        return new Response(json_encode(['revision' => $revision], JSON_THROW_ON_ERROR), 200, ['Content-Type' => 'application/json']);
    }

    private static function authorized(Request $request): bool
    {
        $secret = getenv('NM_OFFERINGS_ADMIN_TOKEN');
        $authorization = $request->headers->get('Authorization');
        return is_string($secret) && strlen($secret) >= 32 && is_string($authorization)
            && hash_equals('Bearer '.$secret, $authorization);
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
            foreach ($row['industries'] as $industry) if (Industry::tryFrom((string) $industry) === null) throw new \InvalidArgumentException('Unknown industry: '.$industry);
            foreach ($row['business_needs'] as $need) if (BusinessNeed::tryFrom((string) $need) === null) throw new \InvalidArgumentException('Unknown business need: '.$need);
            if (!is_array($row['prices']) || !is_array($row['relations']) || !is_bool($row['active'])) throw new \InvalidArgumentException('Prices, relations, or active flag have invalid types.');
        }
    }
}
