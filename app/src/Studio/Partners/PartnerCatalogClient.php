<?php declare(strict_types=1);

namespace App\Studio\Partners;

/** Synchronizes one partner's API response into a local last-known-good snapshot. */
final class PartnerCatalogClient
{
    /** @param callable(?string):array{status:int,etag?:string,body?:array} $fetch */
    public function sync(string $partnerId, PartnerSnapshotStore $store, callable $fetch): array
    {
        $previous = $store->read($partnerId);
        try {
            $response = $fetch($previous['etag'] ?? null);
            if (($response['status'] ?? 0) === 304 && $previous !== null) return $previous['payload'];
            if (($response['status'] ?? 0) !== 200 || !is_array($response['body'] ?? null) || !is_string($response['etag'] ?? null)) {
                throw new \RuntimeException('Partner API returned an invalid catalogue response.');
            }
            $body = $response['body'];
            if (($body['schema_version'] ?? null) !== 1 || !is_int($body['catalog_revision'] ?? null) || !is_array($body['offerings'] ?? null)) {
                throw new \RuntimeException('Partner API catalogue schema is invalid.');
            }
            $store->write($partnerId, $body, $response['etag']);
            return $body;
        } catch (\Throwable $error) {
            if ($previous !== null) return $previous['payload'];
            throw $error;
        }
    }
}
