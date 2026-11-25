<?php declare(strict_types=1);

$root = dirname(__DIR__, 5);
require $root.'/internal/dependencies/autoload.php';
require dirname(__DIR__, 3).'/src/SnapshotStore.php';
require dirname(__DIR__, 3).'/src/CatalogueClient.php';
require dirname(__DIR__).'/Storefront.php';

use App\Studio\Http\PartnerOfferingController;
use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;
use Numerimondes\Offerings\CatalogueClient;
use Numerimondes\Offerings\SnapshotStore;
use Webkernel\Route\Request;

$registry = $root.'/config/partners.php';
if (is_file($registry)) throw new RuntimeException('Do not run partner example integration while a local partner registry exists.');
$temp = sys_get_temp_dir().'/nm-partner-example-'.bin2hex(random_bytes(5));
mkdir($temp, 0700, true);
$token = str_repeat('example-token-', 4);
file_put_contents($registry, '<?php return '.var_export([[
    'id' => 'example-storefront', 'name' => 'Fictional integration partner', 'enabled' => true,
    'secret_hash' => hash('sha256', $token), 'domains' => ['store.example.test'],
    'offering_ids' => ['service-parent', 'x-addon'],
]], true).';');
try {
    OfferingStore::initialize($temp.'/catalogue.sqlite');
    QuoteStore::initialize($temp.'/catalogue.sqlite');
    $store = new OfferingStore($temp.'/catalogue.sqlite');
    $store->saveDraft(['offerings' => [
        ['id' => 'service-parent', 'type' => 'service', 'name' => ['en' => 'Parent service'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [['id' => 'base', 'amount_mad' => 10000, 'billing' => 'one_time', 'unit' => 'project']], 'relations' => [['offering_id' => 'x-addon', 'type' => 'extension', 'optional' => true, 'visibility' => 'detail_only']], 'active' => true, 'visibility' => 'catalog'],
        ['id' => 'x-addon', 'type' => 'service', 'name' => ['en' => 'Optional extension'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [['id' => 'addon', 'amount_mad' => 2500, 'billing' => 'one_time', 'unit' => 'project']], 'relations' => [], 'active' => true, 'visibility' => 'detail-only'],
    ]], 'integration-test');
    $store->publish('integration-test');
    $getTransport = static function (string $url, string $credential, ?string $etag, int $timeout) use ($token, $store): array {
        $response = PartnerOfferingController::index(new Request('GET', '/api/v1/offerings', headers: [
            'authorization' => 'Bearer '.$credential, 'origin' => 'https://store.example.test',
            ...($etag === null ? [] : ['if-none-match' => $etag]),
        ]), $store);
        return ['status' => $response->getStatusCode(), 'body' => $response->getContent(), 'etag' => $response->headers->get('ETag')];
    };
    $postTransport = static function (string $url, string $credential, array $body, int $timeout) use ($token, $store, $temp): array {
        $response = PartnerOfferingController::quote(new Request('POST', '/api/v1/quotes', headers: [
            'authorization' => 'Bearer '.$credential, 'origin' => 'https://store.example.test',
        ], body: $body), $store, new QuoteStore($temp.'/catalogue.sqlite'));
        return ['status' => $response->getStatusCode(), 'body' => $response->getContent()];
    };
    $client = new CatalogueClient('https://api.example.test', 'example-storefront', $token, new SnapshotStore($temp.'/cache'), 3, $getTransport, $postTransport);
    $storefront = new PartnerStorefront($client);
    $catalogue = $storefront->catalogue();
    if (count($catalogue['offerings']) !== 1 || ($catalogue['offerings'][0]['extensions'][0]['id'] ?? null) !== 'x-addon') throw new RuntimeException('Storefront did not receive nested detail-only extension through partner API.');
    $quote = $storefront->quote($catalogue, [['offering_id' => 'service-parent', 'quantity' => 2], ['offering_id' => 'x-addon', 'quantity' => 3]], 'MAD');
    if (count($quote['lines']) !== 2 || ($quote['lines'][1]['parent_id'] ?? null) !== 'service-parent'
        || ($quote['lines'][1]['quantity'] ?? null) !== 3 || ($quote['lines'][1]['prices'][0]['unit_amount'] ?? null) !== 2500
        || ($quote['lines'][1]['prices'][0]['amount'] ?? null) !== 7500) throw new RuntimeException('Storefront quote did not use the actual partner quote controller, preserve quantities, and calculate extended unit pricing.');
    try { $storefront->quote($catalogue, [['offering_id' => 'x-addon', 'quantity' => 1]], 'MAD'); throw new RuntimeException('Storefront allowed an extension without its parent.'); }
    catch (InvalidArgumentException) {}

    $unavailable = new PartnerStorefront(new CatalogueClient('https://api.example.test', 'example-storefront', $token, new SnapshotStore($temp.'/empty-cache'), 1,
        static fn () => throw new RuntimeException('API outage')));
    try { $unavailable->catalogue(); throw new RuntimeException('Storefront should have no catalogue when API fails without a cached snapshot.'); }
    catch (RuntimeException $error) { if ($error->getMessage() !== 'API outage') throw $error; }
    echo "Partner storefront integration passed against the real API controllers.\n";
} finally {
    @unlink($registry);
    foreach (glob($temp.'/*') ?: [] as $file) {
        if (is_dir($file)) { foreach (glob($file.'/*') ?: [] as $nested) @unlink($nested); @rmdir($file); }
        else @unlink($file);
    }
    @rmdir($temp);
}
