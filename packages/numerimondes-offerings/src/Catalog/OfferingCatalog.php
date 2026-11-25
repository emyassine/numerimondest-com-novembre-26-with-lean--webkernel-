<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Catalog;

use Numerimondes\Offerings\Persistence\OfferingStore;

/** The single runtime read model for public offerings. */
final class OfferingCatalog
{
    /** @return array<string,array<string,mixed>> */
    public static function all(string $status = 'published'): array
    {
        static $cache = [];
        if (isset($cache[$status])) return $cache[$status];
        $store = new OfferingStore();
        $revision = $store->current($status);
        if ($revision === null && $status === 'published') {
            throw new \RuntimeException('No published offerings catalogue exists; initialize it with php scripts/migrate-canonical-offerings.php.');
        }
        if ($revision === null) return $cache[$status] = [];
        $items = [];
        foreach (($revision['catalogue']['offerings'] ?? []) as $item) {
            if (is_array($item) && is_string($item['id'] ?? null)) $items[$item['id']] = $item;
        }
        return $cache[$status] = $items;
    }

    /** @return array<string,mixed> */
    public static function find(string $id): array
    {
        $offering = self::all()[$id] ?? null;
        if ($offering === null) throw new \RuntimeException('Offering not found: '.$id);
        return $offering;
    }

    /** @return array<string,mixed>|null */
    public static function parentOf(string $id, ?string $relationType = null): ?array
    {
        foreach (self::all() as $offering) {
            foreach (($offering['relations'] ?? []) as $relation) {
                if (($relation['offering_id'] ?? null) !== $id || ($relationType !== null && ($relation['type'] ?? null) !== $relationType)) continue;
                if (($relation['visibility'] ?? 'detail_only') === 'hidden') continue;
                return $offering;
            }
        }
        return null;
    }

}
