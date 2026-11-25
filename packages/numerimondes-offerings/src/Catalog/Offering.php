<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Catalog;

use Numerimondes\Offerings\Catalog\Taxonomy\BusinessNeed;
use Numerimondes\Offerings\Catalog\Taxonomy\Industry;

/** Canonical catalog record. Translation maps stay unresolved until rendering/API serialization. */
final readonly class Offering
{
    /** @param array<string,string> $name @param array<string,string> $description
     * @param list<string> $industries @param list<string> $businessNeeds
     * @param list<PriceComponent> $prices @param array<string,mixed> $attributes
     */
    public function __construct(
        public string $id,
        public OfferingType $type,
        public array $name,
        public array $description,
        public array $industries,
        public array $businessNeeds,
        public array $prices = [],
        public array $attributes = [],
        public bool $active = true,
    ) {
        if ($id === '' || !preg_match('/^[a-z0-9][a-z0-9-]*$/', $id)) {
            throw new \InvalidArgumentException('Offering IDs must be stable kebab-case identifiers.');
        }
        foreach ($industries as $industry) {
            if (Industry::tryFrom($industry) === null) throw new \InvalidArgumentException("Unknown industry: {$industry}");
        }
        foreach ($businessNeeds as $need) {
            if (BusinessNeed::tryFrom($need) === null) throw new \InvalidArgumentException("Unknown business need: {$need}");
        }
    }

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        $type = OfferingType::tryFrom((string) ($row['type'] ?? ''));
        if ($type === null) throw new \InvalidArgumentException('Offering type is missing or invalid.');
        $prices = [];
        foreach (($row['prices'] ?? []) as $price) {
            if (!is_array($price)) throw new \InvalidArgumentException('Offering price components must be objects.');
            $prices[] = new PriceComponent(
                (string) ($price['id'] ?? ''),
                is_numeric($price['amount_mad'] ?? null) ? (float) $price['amount_mad'] : null,
                (string) ($price['billing'] ?? 'one_time'),
                is_numeric($price['minimum_mad'] ?? null) ? (int) $price['minimum_mad'] : null,
                is_numeric($price['maximum_mad'] ?? null) ? (int) $price['maximum_mad'] : null,
                is_numeric($price['percentage'] ?? null) ? (float) $price['percentage'] : null,
                is_array($price['basis'] ?? null) ? $price['basis'] : null,
                is_array($price['label'] ?? null) ? $price['label'] : null,
                is_string($price['unit'] ?? null) ? $price['unit'] : null,
            );
        }
        $name = is_array($row['name'] ?? null) ? $row['name'] : [];
        $description = is_array($row['description'] ?? null) ? $row['description'] : [];
        $industries = is_array($row['industries'] ?? null) ? array_values($row['industries']) : [];
        $needs = is_array($row['business_needs'] ?? null) ? array_values($row['business_needs']) : [];
        return new self(
            (string) ($row['id'] ?? ''), $type, $name, $description, $industries, $needs, $prices,
            $row, ($row['active'] ?? false) === true,
        );
    }
}
