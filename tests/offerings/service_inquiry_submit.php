<?php declare(strict_types=1);

use Webkernel\Config\Config;

$root = dirname(__DIR__, 2);
$base_path = $root;
$GLOBALS['BASE_PATH'] = $root;
$sessionPath = $root.'/runtime/sessions/inquiry-submit-'.bin2hex(random_bytes(4));
mkdir($sessionPath, 0700, true);
ini_set('session.save_path', sys_get_temp_dir());
$database = sys_get_temp_dir().'/nm-inquiry-submit-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require $root.'/internal/dependencies/autoload.php';
Config::boot($root, $root.'/internal/dependencies', $root.'/runtime/cache');
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);

use App\Studio\Http\ServiceInquiryController;
use Numerimondes\Offerings\Persistence\QuoteStore;
use Webkernel\Route\Request;
use Webkernel\Session\Session;

Session::configure(['cookie' => 'NMInquiryTest', 'save_path' => $sessionPath, 'lifetime' => 120, 'same_site' => 'Lax', 'http_only' => true, 'secure' => false]);
Session::put('numerimondes.service_inquiry.services', ['sys-sprint', 'x-sprint-flow']);
$lead = null;
$request = new Request('POST', '/service-inquiry', body: [
    'name' => 'Test Client', 'email' => 'client@example.test', 'company' => 'Example Ltd',
    'message' => 'Please discuss three workflow automations for our operations.',
    'privacy_consent' => '1', 'quote_currency' => 'MAD', 'quantities' => ['x-sprint-flow' => '3'],
]);
$response = ServiceInquiryController::submit($request, static function (array $value) use (&$lead): bool {
    $lead = $value;
    return true;
});
if ($response->getStatusCode() !== 303 || !is_array($lead)) throw new RuntimeException('A valid service enquiry did not create and deliver a lead. Status '.$response->getStatusCode().' error '.(string)Session::get('numerimondes.service_inquiry.error', 'none'));
if (!str_contains($lead['message'], 'Extension · Operational Workflow Sprint')
    || !str_contains($lead['message'], 'Quantity: 3 workflow')
    || !str_contains($lead['message'], 'Offer quote reference: q_')) {
    throw new RuntimeException('The delivered lead lost its parent, selected quantity, or immutable quote reference.');
}
preg_match('/Offer quote reference: (q_[a-f0-9]+)/', $lead['message'], $match);
$savedQuote = isset($match[1]) ? (new QuoteStore())->find($match[1]) : null;
if ($savedQuote === null || ($savedQuote->lines[1]['quantity'] ?? null) !== 3
    || (float) ($savedQuote->lines[1]['prices'][0]['amount'] ?? 0) !== 27000.0) {
    throw new RuntimeException('The submitted lead quote snapshot did not preserve its extended unit price.');
}

session_write_close();
foreach (glob($sessionPath.'/*') ?: [] as $file) @unlink($file);
@rmdir($sessionPath);
putenv('NM_OFFERINGS_DATABASE');
foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
echo "Service enquiry submit workflow passed.\n";
