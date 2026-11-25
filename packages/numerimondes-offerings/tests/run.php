<?php declare(strict_types=1);

require dirname(__DIR__).'/src/SnapshotStore.php';
require dirname(__DIR__).'/src/CatalogueClient.php';

use Numerimondes\Offerings\CatalogueClient;
use Numerimondes\Offerings\SnapshotStore;

$directory = sys_get_temp_dir().'/nm-offerings-test-'.bin2hex(random_bytes(5));
$store = new SnapshotStore($directory);
$payload = ['schema_version' => 1, 'catalog_revision' => 4, 'offerings' => [['id' => 'brand-system', 'name' => ['en' => 'Brand system']]]];
CatalogueClient::validatePayload($payload);
$store->write('partner-one', $payload, '"rev-4"');
if ($store->read('partner-one')['payload'] !== $payload) throw new RuntimeException('Snapshot round-trip failed.');
if ($store->read('partner-two') !== null) throw new RuntimeException('Snapshot was not isolated by partner.');
foreach ([
    ['schema_version' => 1, 'catalog_revision' => 0, 'offerings' => []],
    ['schema_version' => 1, 'catalog_revision' => 4, 'offerings' => [['id'=>'duplicate'], ['id'=>'duplicate']]],
] as $invalid) {
    try { CatalogueClient::validatePayload($invalid); throw new RuntimeException('Invalid payload was accepted.'); }
    catch (UnexpectedValueException) {}
}

// Exercise synchronization, conditional ETag requests, and last-known-good recovery.
$requests = 0;
$testToken = str_repeat('t', 48);
$badCredentialRejected = false;
try { new CatalogueClient('https://api.example.test', 'partner-one', "token\r\nInjected: yes", $store); }
catch (InvalidArgumentException) { $badCredentialRejected = true; }
if (!$badCredentialRejected) throw new RuntimeException('Unsafe bearer token characters were accepted.');
$transport = static function (string $url, string $token, ?string $etag) use (&$requests, $payload, $testToken): array {
    $requests++;
    if ($token !== $testToken) throw new RuntimeException('Bearer token was not passed to transport.');
    if ($etag === '"rev-4"') return ['status' => 304, 'body' => '', 'etag' => '"rev-4"'];
    return ['status' => 200, 'body' => json_encode($payload, JSON_THROW_ON_ERROR), 'etag' => '"rev-4"'];
};
$client = new CatalogueClient('https://api.example.test', 'partner-one', $testToken, $store, 2, $transport);
if ($client->sync() !== $payload) throw new RuntimeException('Synchronization did not return the API catalogue.');
if ($client->sync() !== $payload || $requests !== 2) throw new RuntimeException('304 response did not reuse the stored snapshot.');
$offlineTransport = static fn () => throw new RuntimeException('API is unavailable.');
$offline = new CatalogueClient('https://api.example.test', 'partner-one', $testToken, $store, 1, $offlineTransport);
if ($offline->sync() !== $payload) throw new RuntimeException('API outage did not recover from the last valid snapshot.');
$quoteCalled = false;
$quoteClient = new CatalogueClient('https://api.example.test', 'partner-one', $testToken, $store, 2, null,
    static function (string $url, string $token, array $request, int $timeout) use (&$quoteCalled, $testToken): array {
        $quoteCalled = true;
        if (!str_ends_with($url, '/api/v1/quotes') || $token !== $testToken || $request['partner_id'] !== 'partner-one' || $request['items'] !== [['offering_id' => 'brand-system', 'quantity' => 3]]) throw new RuntimeException('Quote request omitted authenticated partner, offering, or quantity attribution.');
        return ['status' => 201, 'body' => json_encode(['quote_id' => 'q1', 'partner_id' => 'partner-one', 'catalog_revision' => '4', 'pricing_revision' => 'p1', 'lines' => [['offering_id' => 'brand-system', 'quantity' => 3, 'unit' => null, 'prices' => [['quantity' => 3, 'unit' => null, 'unit_amount' => 100, 'amount' => 300]]]]], JSON_THROW_ON_ERROR)];
    });
if (($quoteClient->requestQuote([['offering_id' => 'brand-system', 'quantity' => 3]])['quote_id'] ?? null) !== 'q1' || !$quoteCalled) throw new RuntimeException('Client did not submit and validate a server quote.');
foreach ([
    [['offering_id' => 'brand-system', 'quantity' => 0]],
    [['offering_id' => 'brand-system', 'quantity' => 1001]],
    [['offering_id' => 'brand-system', 'quantity' => 1.5]],
    [['offering_id' => 'brand-system', 'quantity' => 1], ['offering_id' => 'brand-system', 'quantity' => 2]],
] as $invalidItems) {
    try { $quoteClient->requestQuote($invalidItems); throw new RuntimeException('Invalid quote item was accepted.'); }
    catch (InvalidArgumentException) {}
}
try { $quoteClient->requestQuote([['offering_id' => 'unknown id', 'quantity' => 1]]); throw new RuntimeException('Invalid quote offering ID was accepted.'); }
catch (InvalidArgumentException) {}

foreach (glob($directory.'/*') ?: [] as $file) @unlink($file);
@rmdir($directory);
echo "Partner package checks passed.\n";
