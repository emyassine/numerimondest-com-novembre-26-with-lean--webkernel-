<?php declare(strict_types=1);

$base_path = dirname(__DIR__, 2);
ini_set('session.save_path', sys_get_temp_dir());
$database = sys_get_temp_dir().'/nm-shortlist-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require $base_path.'/internal/dependencies/autoload.php';
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($base_path);

use App\Studio\Shortlist\ServiceShortlist;

ServiceShortlist::save(['sales-desk', 'x-desk-mo', 'not-an-offering', 'sales-desk']);
$selected = ServiceShortlist::all();
if ($selected !== ['sales-desk', 'x-desk-mo']) {
    throw new RuntimeException('Shortlist failed to preserve canonical offerings, extension IDs, or de-duplicate selections.');
}
ServiceShortlist::save(['x-desk-mo']);
if (ServiceShortlist::all() !== ['x-desk-mo']) {
    throw new RuntimeException('Removing a parent offering must not silently remove its shortlisted extension.');
}
ServiceShortlist::save([]);
if (ServiceShortlist::all() !== []) throw new RuntimeException('Clearing the shortlist did not remove saved selections.');
$_COOKIE['nm_studio_service_shortlist'] = json_encode(['x-desk-mo'], JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_URI'] = '/shortlist';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
require $base_path.'/public/index.php';
$html = (string) ob_get_clean();
if (http_response_code() !== 200 || !str_contains($html, 'Extension · Revenue Desk · 90-Day Programme') || !str_contains($html, '/services/sales-desk')) {
    throw new RuntimeException('The shortlist page did not render a detail-only extension with its parent label and parent detail link.');
}
putenv('NM_OFFERINGS_DATABASE');
foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
echo "Shortlist persistence passed.\n";
