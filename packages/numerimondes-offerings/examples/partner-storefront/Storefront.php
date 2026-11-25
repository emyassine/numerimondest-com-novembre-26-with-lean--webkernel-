<?php declare(strict_types=1);

use Numerimondes\Offerings\CatalogueClient;

/** Small server-side storefront adapter; all commercial prices come from the central quote API. */
final class PartnerStorefront
{
    public function __construct(private readonly CatalogueClient $client) {}

    /** Returns the last known good published catalogue or lets the page show an unavailable state. */
    public function catalogue(): array
    {
        return $this->client->sync();
    }

    /** @param list<array{offering_id:string,quantity:int}> $items */
    public function quote(array $catalogue, array $items, string $currency): array
    {
        if ($items === [] || count($items) > 30) {
            throw new InvalidArgumentException('Choose one or more offerings.');
        }
        $selectedIds = [];
        $seenIds = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_string($item['offering_id'] ?? null) || !is_int($item['quantity'] ?? null)
                || $item['quantity'] < 1 || $item['quantity'] > 1000 || isset($seenIds[$item['offering_id']])) {
                throw new InvalidArgumentException('Each selected offering needs a unique ID and quantity from 1 to 1000.');
            }
            $selectedIds[] = $item['offering_id'];
            $seenIds[$item['offering_id']] = true;
        }
        $visible = [];
        $parentByExtension = [];
        foreach ($catalogue['offerings'] ?? [] as $offering) {
            if (!is_array($offering) || !is_string($offering['id'] ?? null)) continue;
            $visible[$offering['id']] = true;
            foreach ($offering['extensions'] ?? [] as $extension) {
                if (is_array($extension) && is_string($extension['id'] ?? null)) {
                    $visible[$extension['id']] = true;
                    $parentByExtension[$extension['id']] = $offering['id'];
                }
            }
        }
        foreach ($selectedIds as $id) {
            if (!is_string($id) || !isset($visible[$id])) throw new InvalidArgumentException('The selection contains an offer not available to this storefront.');
            if (isset($parentByExtension[$id]) && !in_array($parentByExtension[$id], $selectedIds, true)) {
                throw new InvalidArgumentException('Select the parent offer with an extension.');
            }
        }
        return $this->client->requestQuote($items, $currency);
    }
}
