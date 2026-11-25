<?php declare(strict_types=1);

namespace App\Studio\Catalog;

use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;

/** Validates catalogue documents before they are saved or published. */
final class OfferingCatalogValidator
{
    /** @param list<array<string,mixed>> $offerings */
    public static function validate(array $offerings): void
    {
        $ids = [];
        $targets = [];
        foreach ($offerings as $row) {
            if (!is_array($row)) throw new \InvalidArgumentException('Each offering must be an object.');
            foreach (['id','type','name','description','industries','business_needs','prices','relations','active'] as $field) {
                if (!array_key_exists($field, $row)) throw new \InvalidArgumentException('Missing offering field: '.$field);
            }
            if (!is_string($row['id']) || !preg_match('/^[a-z0-9][a-z0-9-]*$/', $row['id']) || isset($ids[$row['id']])) {
                throw new \InvalidArgumentException('Offering IDs must be unique kebab-case strings.');
            }
            $ids[$row['id']] = true;
            if (OfferingType::tryFrom((string) $row['type']) === null) throw new \InvalidArgumentException('Unknown offering type.');
            foreach (['name','description'] as $field) {
                if (!is_array($row[$field]) || $row[$field] === []) throw new \InvalidArgumentException($field.' must contain locale translations.');
                foreach ($row[$field] as $locale => $text) {
                    if (!is_string($locale) || !preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $locale) || !is_string($text)) {
                        throw new \InvalidArgumentException('Translations must map locale codes to strings.');
                    }
                }
            }
            if (!is_array($row['industries']) || !is_array($row['business_needs']) || !is_array($row['prices']) || !is_array($row['relations']) || !is_bool($row['active'])) {
                throw new \InvalidArgumentException('Classifications, prices, relations, or active flag have invalid types.');
            }
            foreach ($row['industries'] as $industry) if (!is_string($industry) || Industry::tryFrom($industry) === null) throw new \InvalidArgumentException('Unknown industry: '.(string) $industry);
            foreach ($row['business_needs'] as $need) if (!is_string($need) || BusinessNeed::tryFrom($need) === null) throw new \InvalidArgumentException('Unknown business need: '.(string) $need);
            foreach ($row['prices'] as $price) {
                if (!is_array($price) || !is_string($price['id'] ?? null) || $price['id'] === '') throw new \InvalidArgumentException('Each price component needs an identifier.');
                if (!in_array($price['billing'] ?? null, ['one_time','monthly','quarterly','annual','percentage'], true)) throw new \InvalidArgumentException('Unknown price billing interval.');
                foreach (['amount_mad','minimum_mad','maximum_mad'] as $amount) {
                    if (isset($price[$amount]) && (!is_numeric($price[$amount]) || !is_finite((float) $price[$amount]) || (float) $price[$amount] < 0)) throw new \InvalidArgumentException('MAD price values must be finite and non-negative.');
                }
                if (isset($price['percentage']) && (!is_numeric($price['percentage']) || (float) $price['percentage'] <= 0 || (float) $price['percentage'] > 100 || !is_array($price['basis'] ?? null))) throw new \InvalidArgumentException('Percentage components require a valid percentage and calculation basis.');
                if (isset($price['minimum_mad'], $price['maximum_mad']) && $price['minimum_mad'] > $price['maximum_mad']) throw new \InvalidArgumentException('Price minimum exceeds maximum.');
                if (isset($price['unit']) && (!is_string($price['unit']) || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $price['unit']) || !is_numeric($price['amount_mad'] ?? null) || isset($price['minimum_mad']) || isset($price['maximum_mad']) || is_numeric($price['percentage'] ?? null))) {
                    throw new \InvalidArgumentException('Unit prices require a fixed amount and a valid unit identifier.');
                }
            }
            foreach ($row['relations'] as $relation) {
                if (!is_array($relation) || !is_string($relation['offering_id'] ?? null) || !is_string($relation['type'] ?? null)
                    || OfferingRelationType::tryFrom($relation['type']) === null
                    || !in_array($relation['visibility'] ?? 'detail_only', ['catalog','detail_only','hidden'], true)
                    || !is_bool($relation['optional'] ?? true)) throw new \InvalidArgumentException('Invalid offering relationship.');
                $targets[$row['id']][] = $relation['offering_id'];
            }
        }
        foreach ($targets as $parent => $children) foreach ($children as $child) {
            if (!isset($ids[$child]) || $parent === $child) throw new \InvalidArgumentException('Offering relationship points to an unknown or identical offering.');
        }
    }
}
