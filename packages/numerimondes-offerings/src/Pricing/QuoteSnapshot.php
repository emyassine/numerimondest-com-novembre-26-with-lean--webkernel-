<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Pricing;

/** Immutable commercial snapshot; never include partner settlement internals. */
final readonly class QuoteSnapshot
{
    public function __construct(
        public string $quoteId,
        public string $partnerId,
        public string $catalogRevision,
        public string $pricingRevision,
        public string $currency,
        public array $lines,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
    ) {
        if ($quoteId === '' || $partnerId === '' || $catalogRevision === '' || $pricingRevision === '') {
            throw new \InvalidArgumentException('Quote snapshot identifiers and revisions are required.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency) || $expiresAt <= $createdAt) {
            throw new \InvalidArgumentException('Quote currency and expiry are invalid.');
        }
        foreach ($lines as $line) {
            if (!is_array($line) || !is_string($line['offering_id'] ?? null) || $line['offering_id'] === '') {
                throw new \InvalidArgumentException('Each quote line needs an offering identifier.');
            }
            if (array_key_exists('quantity', $line) && (!is_int($line['quantity']) || $line['quantity'] < 1 || $line['quantity'] > 1000)) {
                throw new \InvalidArgumentException('Quote line quantity must be an integer between 1 and 1000.');
            }
            if (isset($line['unit']) && (!is_string($line['unit']) || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $line['unit']))) {
                throw new \InvalidArgumentException('Quote line unit is invalid.');
            }
            if (($line['quantity'] ?? 1) > 1 && !isset($line['unit'])) {
                throw new \InvalidArgumentException('A quote line with multiple units must name its unit.');
            }
        }
    }

    public function toArray(): array
    {
        return ['quote_id'=>$this->quoteId,'partner_id'=>$this->partnerId,'catalog_revision'=>$this->catalogRevision,
            'pricing_revision'=>$this->pricingRevision,'currency'=>$this->currency,'lines'=>$this->lines,
            'created_at'=>$this->createdAt->format(DATE_ATOM),'expires_at'=>$this->expiresAt->format(DATE_ATOM)];
    }
}
