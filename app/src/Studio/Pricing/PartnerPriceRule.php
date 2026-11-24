<?php declare(strict_types=1);

namespace App\Studio\Pricing;

/** Public-facing partner adjustment; internal commission/revenue data is never serialized. */
final readonly class PartnerPriceRule
{
    public function __construct(
        public string $partnerId,
        public string $offeringId,
        public string $mode = 'inherit',
        public ?int $fixedMad = null,
        public float $adjustmentPercent = 0.0,
        public ?int $minimumMad = null,
        public ?int $maximumMad = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {
        if (!in_array($mode, ['inherit', 'replace', 'adjust_percent'], true)) {
            throw new \InvalidArgumentException('Unsupported partner pricing mode.');
        }
        if ($mode === 'replace' && ($fixedMad === null || $fixedMad < 0)) {
            throw new \InvalidArgumentException('Replacement pricing requires a non-negative MAD amount.');
        }
        if ($minimumMad !== null && $maximumMad !== null && $minimumMad > $maximumMad) {
            throw new \InvalidArgumentException('Partner price floor exceeds its ceiling.');
        }
    }

    public function appliesAt(\DateTimeImmutable $at): bool
    {
        return ($this->startsAt === null || $at >= new \DateTimeImmutable($this->startsAt))
            && ($this->endsAt === null || $at <= new \DateTimeImmutable($this->endsAt));
    }

    public function resolve(int $baseMad): int
    {
        $value = match ($this->mode) {
            'replace' => $this->fixedMad,
            'adjust_percent' => (int) round($baseMad * (1 + $this->adjustmentPercent / 100)),
            default => $baseMad,
        };
        if ($this->minimumMad !== null) $value = max($value, $this->minimumMad);
        if ($this->maximumMad !== null) $value = min($value, $this->maximumMad);
        return $value;
    }
}
