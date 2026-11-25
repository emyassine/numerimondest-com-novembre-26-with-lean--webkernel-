<?php declare(strict_types=1);

$base_path = dirname(__DIR__, 2);
ini_set('session.save_path', sys_get_temp_dir());
$catalogDatabase = sys_get_temp_dir().'/nm-inquiry-catalog-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$catalogDatabase);
require $base_path.'/internal/dependencies/autoload.php';
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($base_path);

use Numerimondes\Offerings\Catalog\OfferingCatalog;
use App\Studio\Http\ServiceInquiryController;
use Numerimondes\Offerings\Persistence\QuoteStore;

$catalogue = OfferingCatalog::all();
$selected = [$catalogue['sales-desk'], $catalogue['x-desk-mo'], $catalogue['rec-ops'], $catalogue['sys-sprint'], $catalogue['x-sprint-flow']];
$quantityValidator = new ReflectionMethod(ServiceInquiryController::class, 'selectedQuantities');
$validatedQuantities = $quantityValidator->invoke(null, ['x-sprint-flow' => '3'], $selected);
if (($validatedQuantities['x-sprint-flow'] ?? null) !== 3 || $quantityValidator->invoke(null, ['x-sprint-flow' => '1001'], $selected) !== null) {
    throw new RuntimeException('Direct enquiry quantity validation did not accept valid counts and reject counts above the bound.');
}
$nonUnitSelection = [$catalogue['sales-desk']];
if ($quantityValidator->invoke(null, ['sales-desk' => '2'], $nonUnitSelection) !== null) {
    throw new RuntimeException('Direct enquiry quantity validation accepted a quantity for an offering without unit pricing.');
}
$method = new ReflectionMethod(ServiceInquiryController::class, 'createQuoteSnapshot');
$quote = $method->invoke(null, $selected, 'MAD', 10000.0, 'MAD', ['x-sprint-flow' => 3]);
$path = sys_get_temp_dir().'/nm-quote-flow-'.bin2hex(random_bytes(8)).'.sqlite';
QuoteStore::initialize($path);
$store = new QuoteStore($path);
$store->save($quote);
$saved = $store->find($quote->quoteId);
if ($saved === null || $saved->catalogRevision === '' || $saved->pricingRevision === '') {
    throw new RuntimeException('The enquiry quote snapshot was not persisted with catalogue and pricing revisions.');
}
if (array_column($saved->lines, 'offering_id') !== ['sales-desk', 'x-desk-mo', 'rec-ops', 'sys-sprint', 'x-sprint-flow']) {
    throw new RuntimeException('The quote snapshot did not preserve selected offerings and extension relationships.');
}
if (($saved->lines[1]['parent_id'] ?? null) !== 'sales-desk' || !isset($saved->lines[1]['parent_name']['en'])) {
    throw new RuntimeException('The quote snapshot lost the canonical parent relationship for an extension.');
}
if (($saved->lines[1]['parent_id'] ?? null) !== 'sales-desk' || ($saved->lines[1]['parent_name']['en'] ?? null) !== 'Revenue Desk · 90-Day Programme') {
    throw new RuntimeException('Extension quote line lost its parent-offering relationship.');
}
$recruitmentPrices = $saved->lines[2]['prices'] ?? [];
if ((float)($recruitmentPrices[0]['amount'] ?? 0) !== 4600.0 || isset($recruitmentPrices[0]['basis_amount']) || stripos((string)($recruitmentPrices[0]['label']['en'] ?? ''), '46%') === false) {
    throw new RuntimeException('The recruitment fee was not calculated or the submitted salary was retained in the snapshot.');
}
$unitLine = $saved->lines[4] ?? [];
$unitPrice = $unitLine['prices'][0] ?? [];
if (($unitLine['quantity'] ?? null) !== 3 || ($unitLine['unit'] ?? null) !== 'workflow'
    || ($unitPrice['quantity'] ?? null) !== 3 || ($unitPrice['unit'] ?? null) !== 'workflow'
    || (float) ($unitPrice['unit_amount'] ?? 0) !== 9000.0 || (float) ($unitPrice['amount'] ?? 0) !== 27000.0
    || ($unitLine['parent_id'] ?? null) !== 'sys-sprint') {
    throw new RuntimeException('Direct enquiry quote did not persist the per-workflow quantity, unit price, extended total, and parent link.');
}
putenv('NM_OFFERINGS_DATABASE');
foreach ([$path, $path.'-wal', $path.'-shm', $catalogDatabase, $catalogDatabase.'-wal', $catalogDatabase.'-shm'] as $file) if (is_file($file)) unlink($file);
echo "Enquiry quote snapshot passed.\n";
