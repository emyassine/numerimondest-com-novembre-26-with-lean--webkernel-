<?php declare(strict_types=1);

namespace App\Studio\Catalog\Migration;

use Numerimondes\Offerings\Persistence\OfferingStore;

/** Refreshes snapshots created only by application seed or migration actors. */
final class RefreshCanonicalOfferings
{
    private const array SAFE_ACTORS = ['system', 'catalog-seed', 'retire-replaced-offerings', 'canonical-seed-refresh'];

    /** @param list<array<string,mixed>> $seed */
    public static function apply(OfferingStore $store, array $seed): ?int
    {
        $published = $store->current('published');
        if ($published === null) {
            $store->saveDraft(['offerings' => RetireReplacedOfferings::retire(['offerings' => $seed])['offerings']], 'catalog-seed');
            return $store->publish('catalog-seed');
        }

        foreach ($store->history() as $entry) {
            if (!in_array($entry['created_by'] ?? '', self::SAFE_ACTORS, true)) {
                throw new \RuntimeException('Published offering history contains a non-seed actor; refusing to replace edited catalogue data.');
            }
        }

        $draft = $store->current('draft');
        if ($draft !== null && self::encode($draft['catalogue']) !== self::encode($published['catalogue'])) {
            throw new \RuntimeException('A divergent draft exists; publish or discard it before refreshing the canonical seed.');
        }

        $currentIds = [];
        foreach (($published['catalogue']['offerings'] ?? []) as $offering) {
            if (!is_array($offering) || !is_string($offering['id'] ?? null)) throw new \RuntimeException('Published offering catalogue is malformed.');
            $currentIds[$offering['id']] = true;
        }
        $seedIds = array_fill_keys(array_column($seed, 'id'), true);
        $unexpected = array_diff_key($currentIds, $seedIds);
        if ($unexpected !== []) {
            throw new \RuntimeException('Published catalogue contains offers absent from the canonical seed; refusing to discard them: '.implode(', ', array_keys($unexpected)));
        }

        $catalogue = RetireReplacedOfferings::retire(['offerings' => $seed]);
        if (self::encode($catalogue) === self::encode($published['catalogue'])) return null;
        $store->saveDraft($catalogue, 'canonical-seed-refresh');
        return $store->publish('canonical-seed-refresh');
    }

    /** @param array<string,mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
