<?php declare(strict_types=1);

namespace App\Studio\Catalog;

final readonly class PriceComponent
{
    /** @param array<string, mixed>|null $basis */
    public function __construct(
        public string $id,
        public int|float|null $amountMad,
        public string $billing = 'one_time',
        public ?int $minimumMad = null,
        public ?int $maximumMad = null,
        public ?float $percentage = null,
        public ?array $basis = null,
        public ?array $label = null,
    ) {
        if (!in_array($billing, ['one_time', 'monthly', 'quarterly', 'annual', 'percentage'], true)) {
            throw new \InvalidArgumentException('Unsupported price billing interval.');
        }
        if ($minimumMad !== null && $maximumMad !== null && $minimumMad > $maximumMad) {
            throw new \InvalidArgumentException('Price minimum cannot exceed maximum.');
        }
    }
}
