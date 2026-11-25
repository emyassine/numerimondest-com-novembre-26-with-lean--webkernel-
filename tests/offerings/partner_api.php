<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use App\Studio\Http\PartnerOfferingController;
use Numerimondes\Offerings\Persistence\OfferingStore;
use Numerimondes\Offerings\Persistence\QuoteStore;
use Webkernel\Route\Request;

function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$configPath = dirname(__DIR__, 2).'/config/partners.php';
if (is_file($configPath)) throw new RuntimeException('Do not run this test while a local partner registry exists.');
$temporary = sys_get_temp_dir().'/nm-api-test-'.bin2hex(random_bytes(6));
mkdir($temporary, 0700, true);
$token = str_repeat('t', 48);
file_put_contents($configPath, '<?php return '.var_export([[
    'id' => 'partner-test', 'name' => 'Test partner', 'enabled' => true,
    'secret_hash' => hash('sha256', $token), 'domains' => ['shop.example.test'],
    'offering_ids' => ['catalog-offer', 'hidden-offer', 'maintenance-plan'],
    'pricing_rules' => ['catalog-offer' => ['mode' => 'adjust_percent', 'adjustment_percent' => 10]],
    'settlement_default' => ['mode' => 'share_percent', 'partner_share_percent' => 15],
]], true).';');
try {
    OfferingStore::initialize($temporary.'/catalog.sqlite');
    $store = new OfferingStore($temporary.'/catalog.sqlite');
    $store->saveDraft(['offerings' => [
        ['id' => 'catalog-offer', 'type' => 'service', 'name' => ['en' => 'Catalog'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [['id' => 'fee', 'amount_mad' => 10000, 'billing' => 'one_time', 'unit' => 'workflow', 'internal' => ['cost_mad' => 8000]]], 'relations' => [['offering_id' => 'hidden-offer', 'type' => 'extension', 'optional' => true, 'visibility' => 'detail_only'], ['offering_id' => 'maintenance-plan', 'type' => 'maintenance', 'optional' => true, 'visibility' => 'detail_only']], 'category' => 'Business systems', 'practice' => 'digital', 'format' => 'Mandate', 'engagement' => 'Project', 'cat' => 'old-category', 'fmt' => 'oneoff', 'tier' => 'old-tier', 'card_description' => 'Legacy copy', 'related_slugs' => ['hidden-offer'], 'active' => true, 'visibility' => 'catalog', 'internal' => ['commission' => 1000], 'source' => ['client_secret' => 'must not be exposed']],
        ['id' => 'hidden-offer', 'type' => 'service', 'name' => ['en' => 'Hidden'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [['id' => 'addon', 'amount_mad' => 2000, 'billing' => 'one_time']], 'relations' => [], 'active' => true, 'visibility' => 'detail-only'],
        ['id' => 'maintenance-plan', 'type' => 'service', 'name' => ['en' => 'Maintenance'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [['id' => 'monthly', 'amount_mad' => 500, 'billing' => 'monthly']], 'relations' => [], 'active' => true, 'visibility' => 'detail-only'],
        ['id' => 'unavailable-offer', 'type' => 'service', 'name' => ['en' => 'Not allowed'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [], 'relations' => [], 'active' => true, 'visibility' => 'catalog'],
    ]], 'test');
    $store->publish('test');
    $request = static fn (array $headers = []): Request => new Request('GET', '/api/v1/offerings', headers: $headers);
    $unauthorized = PartnerOfferingController::index($request(['authorization' => 'Bearer short']), $store);
    verify($unauthorized->getStatusCode() === 401, 'Invalid credentials must be rejected.');
    $forbiddenOrigin = PartnerOfferingController::index($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://evil.example.test']), $store);
    verify($forbiddenOrigin->getStatusCode() === 401, 'Unlisted browser origins must be rejected.');

    $response = PartnerOfferingController::index($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test']), $store);
    verify($response->getStatusCode() === 200, 'Enabled partner credentials and origin should authenticate.');
    $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    verify(count($payload['offerings']) === 1 && $payload['offerings'][0]['id'] === 'catalog-offer', 'Partner scope and detail-only visibility must be enforced.');
    $detail = PartnerOfferingController::show($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test']), 'catalog-offer', $store);
    verify($detail->getStatusCode() === 200, 'An individually authorized published offering must be available by ID.');
    $detailPayload = json_decode($detail->getContent(), true, 512, JSON_THROW_ON_ERROR);
    verify(($detailPayload['offering']['id'] ?? null) === 'catalog-offer' && !isset($detailPayload['offerings']), 'The offering detail endpoint must return only the requested authorized offering.');
    $detailEtag = $detail->headers->get('ETag');
    $cachedDetail = PartnerOfferingController::show($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test', 'if-none-match' => $detailEtag]), 'catalog-offer', $store);
    verify($cachedDetail->getStatusCode() === 304, 'The offering detail endpoint must honor its ETag.');
    verify(PartnerOfferingController::show($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test']), 'unavailable-offer', $store)->getStatusCode() === 404, 'An offering outside a partner allowlist must not be readable by ID.');
    $maintenance = PartnerOfferingController::show($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test']), 'maintenance-plan', $store);
    $maintenancePayload = json_decode($maintenance->getContent(), true, 512, JSON_THROW_ON_ERROR);
    verify($maintenance->getStatusCode() === 200 && ($maintenancePayload['offering']['parent']['id'] ?? null) === 'catalog-offer'
        && ($maintenancePayload['offering']['parent']['relation']['type'] ?? null) === 'maintenance', 'An authorized detail-only maintenance offering must resolve only through its allowed parent relationship.');
    $maintenanceCached = PartnerOfferingController::show($request(['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test']), 'maintenance-plan', $store);
    verify(str_contains((string) $maintenanceCached->headers->get('Cache-Control'), 'private') && (string) $maintenanceCached->headers->get('ETag') !== '', 'Related offering details must use partner-private caching and an ETag.');
    verify((float) $payload['offerings'][0]['prices'][0]['amount_mad'] === 11000.0, 'The configured partner price must be applied in MAD.');
    verify(($payload['offerings'][0]['prices'][0]['unit'] ?? null) === 'workflow', 'The public catalogue must expose canonical per-unit pricing metadata.');
    $attributes = $payload['offerings'][0]['attributes'] ?? [];
    verify(($attributes['practice'] ?? null) === 'digital' && ($attributes['format'] ?? null) === 'Mandate', 'Canonical partner attributes were not serialized.');
    foreach (['cat', 'fmt', 'tier', 'engagement_type', 'card_description', 'related_slugs'] as $legacyKey) {
        verify(!array_key_exists($legacyKey, $attributes), 'A legacy catalogue key was exposed to partners: '.$legacyKey);
    }
    verify(!array_key_exists('internal', $payload['offerings'][0]) && !array_key_exists('source', $payload['offerings'][0]) && !array_key_exists('internal', $payload['offerings'][0]['prices'][0]), 'Internal commercial terms and unreviewed source fields must not be exposed.');
    verify(count($payload['offerings'][0]['extensions']) === 1 && $payload['offerings'][0]['extensions'][0]['id'] === 'hidden-offer', 'A detail-only extension must be nested below its allowed parent.');
    verify(!array_key_exists('internal', $payload['offerings'][0]['extensions'][0]), 'Nested extension must not expose internal data.');
    $etag = $response->headers->get('ETag');
    $cached = PartnerOfferingController::index($request(['authorization' => 'Bearer '.$token, 'if-none-match' => $etag]), $store);
    verify($cached->getStatusCode() === 304, 'Matching ETag must return not modified.');
    $post = static fn (array $body): Request => new Request('POST', '/api/v1/quotes', headers: ['authorization' => 'Bearer '.$token, 'origin' => 'https://shop.example.test'], body: $body);
    QuoteStore::initialize($temporary.'/quotes.sqlite');
    $quotes = new QuoteStore($temporary.'/quotes.sqlite');
    $legacyPayload = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'offering_ids' => ['catalog-offer']]), $store, $quotes);
    verify($legacyPayload->getStatusCode() === 422, 'The retired offering_ids quote request shape must not be accepted.');
    $standalone = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'hidden-offer', 'quantity' => 1]]]), $store, $quotes);
    verify($standalone->getStatusCode() === 422, 'Detail-only extensions cannot be quoted without their parent.');
    $quoteResponse = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => 1], ['offering_id' => 'hidden-offer', 'quantity' => 1]]]), $store, $quotes);
    verify($quoteResponse->getStatusCode() === 201, 'Partner quote request should produce a server-authoritative quote.');
    $maintenanceQuote = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => 1], ['offering_id' => 'maintenance-plan', 'quantity' => 1]]]), $store, $quotes);
    verify($maintenanceQuote->getStatusCode() === 201, 'A partner must be able to quote an authorized maintenance offering together with its parent.');
    $quote = json_decode($quoteResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
    verify($quote['partner_id'] === 'partner-test' && count($quote['lines']) === 2 && (float) $quote['lines'][0]['prices'][0]['amount'] === 11000.0 && ($quote['lines'][0]['quantity'] ?? null) === 1 && ($quote['lines'][0]['unit'] ?? null) === 'workflow', 'Quote must bind partner, quantity, unit, offerings, and adjusted customer price.');
    $three = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => 3]]]), $store, $quotes);
    $threeQuote = json_decode($three->getContent(), true, 512, JSON_THROW_ON_ERROR);
    verify($three->getStatusCode() === 201 && ($threeQuote['lines'][0]['quantity'] ?? null) === 3 && ($threeQuote['lines'][0]['prices'][0]['unit'] ?? null) === 'workflow'
        && (float) $threeQuote['lines'][0]['prices'][0]['unit_amount'] === 11000.0 && (float) $threeQuote['lines'][0]['prices'][0]['amount'] === 33000.0,
        'Three unit-priced workflows must produce an authoritative extended quote with preserved unit amount.');
    foreach ([0, 1001, '3'] as $invalidQuantity) {
        $invalid = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => $invalidQuantity]]]), $store, $quotes);
        verify($invalid->getStatusCode() === 422, 'Invalid or out-of-range quantities must be rejected.');
    }
    $nonUnit = PartnerOfferingController::quote($post(['partner_id' => 'partner-test', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => 1], ['offering_id' => 'hidden-offer', 'quantity' => 2]]]), $store, $quotes);
    verify($nonUnit->getStatusCode() === 422, 'A non-unit-priced offering must reject quantities greater than one.');
    verify(!str_contains($quoteResponse->getContent(), 'settlement') && !str_contains($quoteResponse->getContent(), 'remuneration') && !str_contains($quoteResponse->getContent(), 'revenue_mad'), 'Partner quote response must not expose internal settlement.');
    $db = new PDO('sqlite:'.$temporary.'/quotes.sqlite');
    $settlements = $db->query('SELECT settlement_json FROM quote_settlements')->fetchAll(PDO::FETCH_COLUMN);
    verify(count($settlements) >= 2 && str_contains(implode('', $settlements), 'partner_remuneration_mad') && str_contains(implode('', $settlements), 'numerimondes_revenue_mad'), 'Internal partner remuneration and Numerimondes revenue must be stored separately.');
    $settlementRecords = array_map(static fn (string $row): array => json_decode($row, true, 512, JSON_THROW_ON_ERROR), $settlements);
    $scaledSettlement = false;
    foreach ($settlementRecords as $record) foreach ($record as $componentLines) foreach ($componentLines as $line) {
        if (($line['quantity'] ?? null) === 3 && ($line['unit'] ?? null) === 'workflow' && (float) ($line['partner_remuneration_mad'] ?? 0) === 4950.0) $scaledSettlement = true;
    }
    verify($scaledSettlement, 'Partner settlement must be calculated against the authoritative quantity-extended MAD total: '.json_encode($settlementRecords));
    $mismatch = PartnerOfferingController::quote($post(['partner_id' => 'other', 'items' => [['offering_id' => 'catalog-offer', 'quantity' => 1]]]), $store, $quotes);
    verify($mismatch->getStatusCode() === 403, 'Quote attribution must reject a partner ID that does not match its credential.');
    echo "Partner API checks passed.\n";
} finally {
    @unlink($configPath);
    foreach (glob($temporary.'/*') ?: [] as $file) @unlink($file);
    @rmdir($temporary);
}
