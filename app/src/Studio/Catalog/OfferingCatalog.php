<?php declare(strict_types=1);

namespace App\Studio\Catalog;

use App\Studio\Persistence\OfferingStore;

/** The single runtime read model for public offerings. */
final class OfferingCatalog
{
    /** @return array<string,array<string,mixed>> */
    public static function all(?string $status = 'published'): array
    {
        $store = new OfferingStore();
        $revision = $store->current($status);
        if ($revision === null && $status === 'published') {
            $seed = self::seedFromExistingData();
            $store->saveDraft(['offerings' => array_values($seed)], 'catalog-seed');
            $store->publish('catalog-seed');
            $revision = $store->current('published');
        }
        if ($revision === null) return [];
        $items = [];
        foreach (($revision['catalogue']['offerings'] ?? []) as $item) {
            if (is_array($item) && is_string($item['id'] ?? null)) $items[$item['id']] = $item;
        }
        return $items;
    }

    /** @return array<string,mixed> */
    public static function find(string $id): array
    {
        $offering = self::all()[$id] ?? null;
        if ($offering === null) throw new \RuntimeException('Offering not found: '.$id);
        return $offering;
    }

    /** One-time importer used only to initialize the new authoritative store. */
    private static function seedFromExistingData(): array
    {
        $legacy = ServiceCatalog::all();
        $items = [];
        foreach ($legacy as $id => $row) {
            $billing = match ($row['billing'] ?? $row['price_interval'] ?? '') {
                'monthly', '/ month' => 'monthly', default => 'one_time'
            };
            $amount = $row['price_amount'] ?? $row['price_mad'] ?? null;
            $items[$id] = [
                'id' => $id,
                'type' => match ($row['type'] ?? '') { 'mandate'=>'mandate','playbook'=>'playbook','subscription'=>'software', default=>'service' },
                'name' => ['en' => (string) ($row['name'] ?? $id)],
                'description' => ['en' => (string) ($row['scope'] ?? $row['description'] ?? '')],
                'industries' => $row['industries'] ?? [],
                'business_needs' => $row['business_needs'] ?? [],
                'prices' => ($amount !== null || isset($row['fee_percentage'])) ? [[
                    'id' => $id.'-base', 'amount_mad' => is_numeric($amount) ? (int) round((float) $amount) : null,
                    'billing' => $billing, 'percentage' => isset($row['fee_percentage']) ? (float) $row['fee_percentage'] : null,
                    'label' => ['en' => (string) ($row['price_text'] ?? '')],
                    'internal' => ['commission'=>null, 'numerimondes_revenue'=>null],
                ]] : [],
                'attributes' => $row,
                'active' => true,
                'visibility' => ($row['visibility'] ?? '') === 'detail-only' ? 'detail-only' : 'catalog',
            ];
        }
        return $items;
    }
}
