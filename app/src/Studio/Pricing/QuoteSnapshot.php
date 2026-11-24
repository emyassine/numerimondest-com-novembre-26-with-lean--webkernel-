<?php declare(strict_types=1);

namespace App\Studio\Pricing;

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
    }

    public function toArray(): array
    {
        return ['quote_id'=>$this->quoteId,'partner_id'=>$this->partnerId,'catalog_revision'=>$this->catalogRevision,
            'pricing_revision'=>$this->pricingRevision,'currency'=>$this->currency,'lines'=>$this->lines,
            'created_at'=>$this->createdAt->format(DATE_ATOM),'expires_at'=>$this->expiresAt->format(DATE_ATOM)];
    }
}
