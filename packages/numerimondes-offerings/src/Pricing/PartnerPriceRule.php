<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Pricing;

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

    /** @param array<string,mixed> $rule */
    public static function fromArray(string $partnerId, string $offeringId, array $rule): self
    {
        $allowed = ['mode', 'fixed_mad', 'adjustment_percent', 'minimum_mad', 'maximum_mad', 'starts_at', 'ends_at'];
        if (array_diff(array_keys($rule), $allowed) !== []) throw new \InvalidArgumentException('Partner pricing rule contains unsupported fields.');
        foreach (['fixed_mad', 'minimum_mad', 'maximum_mad'] as $field) {
            if (isset($rule[$field]) && (!is_int($rule[$field]) || $rule[$field] < 0)) throw new \InvalidArgumentException('Partner price bounds must be non-negative MAD integers.');
        }
        if (isset($rule['adjustment_percent']) && (!is_numeric($rule['adjustment_percent']) || !is_finite((float) $rule['adjustment_percent']) || (float) $rule['adjustment_percent'] < -100 || (float) $rule['adjustment_percent'] > 1000)) {
            throw new \InvalidArgumentException('Partner percentage adjustment must be between -100 and 1000.');
        }
        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($rule[$field]) && (!is_string($rule[$field]) || trim($rule[$field]) === '')) throw new \InvalidArgumentException('Partner pricing dates must be non-empty ISO date strings.');
        }
        return new self(
            $partnerId,
            $offeringId,
            (string) ($rule['mode'] ?? 'inherit'),
            isset($rule['fixed_mad']) ? (int) $rule['fixed_mad'] : null,
            (float) ($rule['adjustment_percent'] ?? 0),
            isset($rule['minimum_mad']) ? (int) $rule['minimum_mad'] : null,
            isset($rule['maximum_mad']) ? (int) $rule['maximum_mad'] : null,
            $rule['starts_at'] ?? null,
            $rule['ends_at'] ?? null,
        );
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
