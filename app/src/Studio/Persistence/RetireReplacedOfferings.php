<?php declare(strict_types=1);

namespace App\Studio\Persistence;

use Numerimondes\Offerings\Persistence\OfferingStore;

/** One-time data migration for offers explicitly replaced by the unified mandate catalogue. */
final class RetireReplacedOfferings
{
    private const array RETIRED_IDS = [
        'automation-sprint', 'brand-book', 'brand-kit-complete', 'brand-kit-essential',
        'crm-client-portal', 'ops-audit', 'revenue-desk-growth', 'revenue-desk-pilot',
        'revenue-desk-scale', 'the-best-recruit', 'the-best-recruit-administrative',
        'the-best-recruit-execution', 'website-build',
    ];

    public static function apply(OfferingStore $store): ?int
    {
        $published = $store->current('published');
        if ($published === null) return null;
        $draft = $store->current('draft');
        if ($draft !== null && $draft['catalogue'] !== $published['catalogue']) {
            throw new \RuntimeException('Publish or discard the divergent draft before running this data migration.');
        }
        $catalogue = self::retire($published['catalogue']);
        if ($catalogue === $published['catalogue']) return null;
        $store->saveDraft($catalogue, 'retire-replaced-offerings');
        return $store->publish('retire-replaced-offerings');
    }

    /** @param array<string,mixed> $catalogue @return array<string,mixed> */
    public static function retire(array $catalogue): array
    {
        $retired = array_fill_keys(self::RETIRED_IDS, true);
        if (!is_array($catalogue['offerings'] ?? null)) throw new \RuntimeException('Published offering catalogue is malformed.');
        foreach ($catalogue['offerings'] as &$offering) {
            if (isset($retired[$offering['id'] ?? '']) && (($offering['active'] ?? true) !== false || ($offering['visibility'] ?? '') !== 'hidden')) {
                $offering['active'] = false;
                $offering['visibility'] = 'hidden';
                $offering['status'] = 'Retired';
            }
        }
        unset($offering);
        return $catalogue;
    }
}
