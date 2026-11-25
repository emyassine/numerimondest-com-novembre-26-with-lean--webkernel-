<?php declare(strict_types=1);

namespace App\Studio\Catalog;

final readonly class OfferingRelation
{
    public function __construct(
        public string $parentId,
        public string $childId,
        public OfferingRelationType $type,
        public bool $optional = true,
        public string $visibility = 'detail_only',
    ) {
        if ($parentId === '' || $childId === '' || $parentId === $childId) {
            throw new \InvalidArgumentException('Offering relations require distinct parent and child identifiers.');
        }
        if (!in_array($visibility, ['catalog', 'detail_only', 'hidden'], true)) {
            throw new \InvalidArgumentException('Unsupported offering relation visibility.');
        }
    }
}
