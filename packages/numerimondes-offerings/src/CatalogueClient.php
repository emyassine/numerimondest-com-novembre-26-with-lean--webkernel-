<?php declare(strict_types=1);

namespace Numerimondes\Offerings;

use Closure;

/** Fetches and validates published snapshots from the Numerimondes partner API. */
final class CatalogueClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $partnerId,
        private readonly string $bearerToken,
        private readonly SnapshotStore $snapshots,
        private readonly int $timeoutSeconds = 8,
        private readonly ?Closure $transport = null,
        private readonly ?Closure $quoteTransport = null,
    ) {
        $parts = parse_url($baseUrl);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? null, ['https', 'http'], true) || !isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new \InvalidArgumentException('The catalogue API URL must be an absolute HTTP(S) URL without credentials or query parameters.');
        }
        if (($parts['scheme'] !== 'https' && !in_array($parts['host'], ['127.0.0.1', 'localhost', '::1'], true))
            || !preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $partnerId)
            || !preg_match('/^[A-Za-z0-9._~-]{32,256}$/', $bearerToken)
            || $timeoutSeconds < 1 || $timeoutSeconds > 60) {
            throw new \InvalidArgumentException('Invalid partner API client configuration. HTTPS is required outside localhost.');
        }
    }

    /** Returns a fresh response, or the isolated last-known-good snapshot during an outage. */
    public function sync(): array
    {
        $previous = $this->snapshots->read($this->partnerId);
        try {
            $response = $this->request($previous['etag'] ?? null);
            if ($response['status'] === 304 && $previous !== null) return $previous['payload'];
            if ($response['status'] !== 200) throw new \RuntimeException('Offering API returned HTTP '.$response['status'].'.');
            $payload = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
            self::validatePayload($payload);
            $etag = $response['etag'];
            if ($etag === null || $etag === '') throw new \RuntimeException('Offering API response did not include an ETag.');
            $this->snapshots->write($this->partnerId, $payload, $etag);
            return $payload;
        } catch (\Throwable $error) {
            if ($previous !== null) return $previous['payload'];
            throw $error;
        }
    }

    /**
     * Requests a centrally validated commercial quote for this authenticated partner.
     * @param list<array{offering_id:string,quantity:int}> $items
     */
    public function requestQuote(array $items, string $currency = 'MAD', array $basisValues = [], ?string $basisCurrency = null): array
    {
        if ($items === [] || count($items) > 30) throw new \InvalidArgumentException('Quote requires between 1 and 30 offering items.');
        $seenIds = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_string($item['offering_id'] ?? null) || !preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $item['offering_id'])
                || !is_int($item['quantity'] ?? null) || $item['quantity'] < 1 || $item['quantity'] > 1000 || isset($seenIds[$item['offering_id']])) {
                throw new \InvalidArgumentException('Quote items need unique offering IDs and integer quantities between 1 and 1000.');
            }
            if (array_diff(array_keys($item), ['offering_id', 'quantity']) !== []) throw new \InvalidArgumentException('Quote item contains unsupported fields.');
            $seenIds[$item['offering_id']] = ['quantity' => $item['quantity']];
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($currency))) throw new \InvalidArgumentException('Quote currency must be an ISO 4217 code.');
        $payload = ['partner_id' => $this->partnerId, 'items' => array_values($items), 'currency' => strtoupper($currency)];
        if ($basisValues !== []) $payload['basis_values'] = $basisValues;
        if ($basisCurrency !== null) $payload['basis_currency'] = strtoupper($basisCurrency);
        $url = rtrim($this->baseUrl, '/').'/api/v1/quotes';
        if ($this->quoteTransport !== null) {
            $response = ($this->quoteTransport)($url, $this->bearerToken, $payload, $this->timeoutSeconds);
        } else {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$this->bearerToken}\r\nAccept: application/json\r\nContent-Type: application/json\r\nUser-Agent: numerimondes-offerings-php/1.0\r\nConnection: close",
                'content' => $body, 'timeout' => $this->timeoutSeconds, 'ignore_errors' => true, 'follow_location' => 0,
            ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
            $responseBody = @file_get_contents($url, false, $context);
            $status = 0;
            foreach ($http_response_header ?? [] as $line) if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $line, $match)) $status = (int) $match[1];
            if ($responseBody === false && $status === 0) throw new \RuntimeException('Unable to reach the Numerimondes quote API.');
            $response = ['status' => $status, 'body' => $responseBody === false ? '' : $responseBody];
        }
        if (!is_array($response) || !is_int($response['status'] ?? null) || !is_string($response['body'] ?? null)) throw new \UnexpectedValueException('Quote transport returned an invalid response.');
        if ($response['status'] !== 201) throw new \RuntimeException('Quote API returned HTTP '.$response['status'].'.');
        $quote = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($quote) || ($quote['partner_id'] ?? null) !== $this->partnerId || !is_string($quote['quote_id'] ?? null)
            || !is_string($quote['catalog_revision'] ?? null) || !is_string($quote['pricing_revision'] ?? null)
            || !is_array($quote['lines'] ?? null)) throw new \UnexpectedValueException('Quote API response schema is invalid.');
        $responseIds = [];
        foreach ($quote['lines'] as $line) {
            $requested = is_array($line) && is_string($line['offering_id'] ?? null) ? ($seenIds[$line['offering_id']] ?? null) : null;
            if (!is_array($line) || !is_int($line['quantity'] ?? null) || $line['quantity'] !== ($requested['quantity'] ?? null)
                || !array_key_exists('unit', $line) || (!is_string($line['unit']) && $line['unit'] !== null)
                || !is_array($line['prices'] ?? null) || isset($responseIds[$line['offering_id']])) throw new \UnexpectedValueException('Quote API returned an unexpected line item or quantity.');
            $responseIds[$line['offering_id']] = true;
            foreach ($line['prices'] as $price) {
                if (!is_array($price) || !is_int($price['quantity'] ?? null) || $price['quantity'] < 1
                    || !array_key_exists('unit', $price) || (!is_string($price['unit']) && $price['unit'] !== null)
                    || (isset($price['amount']) && $price['amount'] !== null && !is_numeric($price['amount']))
                    || (isset($price['unit_amount']) && $price['unit_amount'] !== null && !is_numeric($price['unit_amount']))) {
                    throw new \UnexpectedValueException('Quote API returned invalid per-unit pricing.');
                }
            }
        }
        if (count($responseIds) !== count($items) || array_diff_key($seenIds, $responseIds) !== []) throw new \UnexpectedValueException('Quote API response omitted requested lines.');
        return $quote;
    }

    /** @param mixed $payload */
    public static function validatePayload(mixed $payload): void
    {
        if (!is_array($payload) || ($payload['schema_version'] ?? null) !== 1 || !is_int($payload['catalog_revision'] ?? null) || $payload['catalog_revision'] < 1 || !is_array($payload['offerings'] ?? null)) {
            throw new \UnexpectedValueException('Offering API catalogue schema is invalid.');
        }
        $ids = [];
        foreach ($payload['offerings'] as $offering) {
            if (!is_array($offering) || !is_string($offering['id'] ?? null) || $offering['id'] === '' || isset($ids[$offering['id']])) {
                throw new \UnexpectedValueException('Offering API catalogue contains an invalid or duplicate offering.');
            }
            $ids[$offering['id']] = true;
        }
    }

    /** @return array{status:int, body:string, etag:?string} */
    private function request(?string $etag): array
    {
        $url = rtrim($this->baseUrl, '/').'/api/v1/offerings';
        if ($this->transport !== null) {
            $result = ($this->transport)($url, $this->bearerToken, $etag, $this->timeoutSeconds);
            if (!is_array($result) || !is_int($result['status'] ?? null) || !is_string($result['body'] ?? null) || (!is_string($result['etag'] ?? null) && ($result['etag'] ?? null) !== null)) {
                throw new \UnexpectedValueException('Injected offering transport returned an invalid response.');
            }
            return ['status' => $result['status'], 'body' => $result['body'], 'etag' => $result['etag'] ?? null];
        }
        $headers = [
            'Authorization: Bearer '.$this->bearerToken,
            'Accept: application/json',
            'User-Agent: numerimondes-offerings-php/1.0',
            'Connection: close',
        ];
        if ($etag !== null) $headers[] = 'If-None-Match: '.$etag;
        $context = stream_context_create(['http' => [
            'method' => 'GET', 'header' => implode("\r\n", $headers),
            'timeout' => $this->timeoutSeconds, 'ignore_errors' => true, 'follow_location' => 0,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $status = 0;
        $responseEtag = null;
        foreach ($responseHeaders as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $line, $match)) $status = (int) $match[1];
            if (stripos($line, 'ETag:') === 0) $responseEtag = trim(substr($line, 5));
        }
        if ($body === false && $status === 0) throw new \RuntimeException('Unable to reach the Numerimondes offering API.');
        return ['status' => $status, 'body' => $body === false ? '' : $body, 'etag' => $responseEtag];
    }
}
