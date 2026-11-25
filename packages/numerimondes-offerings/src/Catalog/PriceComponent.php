<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Catalog;

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
        public ?string $unit = null,
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('Price component ID cannot be empty.');
        }
        if (!in_array($billing, ['one_time', 'monthly', 'quarterly', 'annual', 'percentage'], true)) {
            throw new \InvalidArgumentException('Unsupported price billing interval.');
        }
        if ($minimumMad !== null && $maximumMad !== null && $minimumMad > $maximumMad) {
            throw new \InvalidArgumentException('Price minimum cannot exceed maximum.');
        }
        if ($amountMad !== null && $amountMad < 0) {
            throw new \InvalidArgumentException('Price amount cannot be negative.');
        }
        if ($percentage !== null && ($percentage <= 0 || $percentage > 100)) {
            throw new \InvalidArgumentException('Price percentage must be greater than zero and at most 100.');
        }
        if ($percentage !== null && !is_array($basis)) {
            throw new \InvalidArgumentException('Percentage prices require an explicit calculation basis.');
        }
        if ($unit !== null && (!preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $unit)
            || $amountMad === null || $percentage !== null || $minimumMad !== null || $maximumMad !== null)) {
            throw new \InvalidArgumentException('Unit prices require a fixed amount and a valid unit identifier.');
        }
    }
}
