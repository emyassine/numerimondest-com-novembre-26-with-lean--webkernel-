<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use App\Studio\Admin\AdminController;
use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;
use Webkernel\Route\Request;
use Webkernel\Session\Session;

$database = sys_get_temp_dir().'/nm-offerings-'.bin2hex(random_bytes(8)).'.sqlite';
OfferingStore::initialize($database);
$path = $database;
QuoteStore::initialize($path);
$store = new OfferingStore($database);
$catalogue = ['offerings' => [['id' => 'workflow-check', 'name' => ['en' => 'Workflow check']]]];
$store->saveDraft($catalogue, 'test');
if (($store->current('draft')['catalogue'] ?? null) !== $catalogue) throw new RuntimeException('Draft read-back failed.');
$first = $store->publish('test');
if (($store->current('published')['revision'] ?? null) !== $first) throw new RuntimeException('First publication did not become current.');
$catalogue['offerings'][0]['name']['en'] = 'Updated workflow check';
$store->saveDraft($catalogue, 'test');
$second = $store->publish('test');
if ($second <= $first || ($store->current('published')['catalogue'] ?? null) !== $catalogue) throw new RuntimeException('Second publication did not replace the live snapshot.');
$history = $store->history();
if (count($history) !== 2 || (int) $history[0]['revision'] !== $second || (int) $history[1]['revision'] !== $first) throw new RuntimeException('Published revision history was not retained in descending order.');

$sessionPath = base_path('runtime/sessions');
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
Session::configure(['cookie' => 'NMAdminWorkflowTest', 'save_path' => $sessionPath, 'lifetime' => 120, 'same_site' => 'Lax', 'http_only' => true, 'secure' => false]);
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$secretPath = base_path('config/offerings-admin.php');
$hadSecret = is_file($secretPath);
if (!$hadSecret) file_put_contents($secretPath, "<?php return ".var_export(['username' => 'test-admin', 'password_hash' => password_hash('test-password-for-suite', PASSWORD_DEFAULT)], true).";\n");
Session::invalidate();
$loginCsrf = bin2hex(random_bytes(32));
Session::put('studio.offerings.admin_csrf', $loginCsrf);
$badLogin = AdminController::login(new Request('POST', '/admin/login', body: ['username' => 'test-admin', 'password' => 'wrong-password', '_csrf' => $loginCsrf]));
if ($badLogin->getStatusCode() !== 401) throw new RuntimeException('Admin login accepted an invalid password.');
$login = AdminController::login(new Request('POST', '/admin/login', body: ['username' => 'test-admin', 'password' => 'test-password-for-suite', '_csrf' => $loginCsrf]));
if ($login->getStatusCode() !== 303 || !is_int(Session::get('studio.offerings.admin_authenticated_at'))) throw new RuntimeException('Admin login failed to establish an authenticated session.');
$csrf = bin2hex(random_bytes(32));
Session::put('studio.offerings.admin_csrf', $csrf);
$validCatalogue = ['offerings' => [[
    'id' => 'admin-offer', 'type' => 'service', 'name' => ['en' => 'Admin offer'], 'description' => ['en' => ''],
    'industries' => ['education-training'], 'business_needs' => ['operations'], 'prices' => [], 'relations' => [], 'active' => true,
]]];
$request = static fn (array $data): Request => new Request('POST', '/admin/offerings/preview', headers: ['x-csrf-token' => $csrf], content: json_encode($data, JSON_THROW_ON_ERROR));
$preview = AdminController::preview($request($validCatalogue));
if ($preview->getStatusCode() !== 200) throw new RuntimeException('Admin preview rejected a valid catalogue.');
$previewData = json_decode($preview->getContent(), true, 512, JSON_THROW_ON_ERROR);
if (($previewData['offering_count'] ?? null) !== 1 || ($previewData['offerings'][0]['name'] ?? null) !== 'Admin offer') throw new RuntimeException('Admin preview omitted a reviewable offering summary.');
$unitCatalogue = $validCatalogue;
$unitCatalogue['offerings'][0]['prices'] = [['id' => 'flow-fee', 'amount_mad' => 9000, 'billing' => 'one_time', 'unit' => 'workflow']];
if (AdminController::preview($request($unitCatalogue))->getStatusCode() !== 200) throw new RuntimeException('Admin preview rejected valid per-unit pricing metadata.');
$invalidUnit = $unitCatalogue;
$invalidUnit['offerings'][0]['prices'][0]['amount_mad'] = null;
$invalidUnit['offerings'][0]['prices'][0]['minimum_mad'] = 8000;
if (AdminController::preview($request($invalidUnit))->getStatusCode() !== 422) throw new RuntimeException('Admin preview accepted a unit price without a fixed amount.');
$invalid = $validCatalogue;
$invalid['offerings'][0]['business_needs'] = ['invented-need'];
if (AdminController::preview($request($invalid))->getStatusCode() !== 422) throw new RuntimeException('Admin preview accepted an invalid taxonomy value.');
$saved = AdminController::saveDraft($request($validCatalogue), $store);
if ($saved->getStatusCode() !== 204 || ($store->current('draft')['catalogue'] ?? null) !== $validCatalogue) throw new RuntimeException('Admin draft endpoint did not persist the validated catalogue.');
$publishedResponse = AdminController::publish(new Request('POST', '/admin/offerings/publish', headers: ['x-csrf-token' => $csrf]), $store);
if ($publishedResponse->getStatusCode() !== 200 || ($store->current('published')['catalogue'] ?? null) !== $validCatalogue) throw new RuntimeException('Admin publish endpoint did not publish the draft.');
if (!$hadSecret) unlink($secretPath);

foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) unlink($file);
echo "Admin publication workflow passed.\n";
