<?php declare(strict_types=1);

namespace App\Studio\Catalog;

use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;

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
}
