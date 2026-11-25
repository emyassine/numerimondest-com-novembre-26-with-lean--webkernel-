<?php declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-inquiry-render-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
$_COOKIE['nm_studio_service_shortlist'] = json_encode(['sales-desk', 'x-desk-mo', 'sys-sprint', 'x-sprint-flow', 'sys-ent', 'x-ent-team'], JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_URI'] = '/service-inquiry';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
try { require $root.'/public/index.php'; }
finally {
    $html = (string) ob_get_clean();
    putenv('NM_OFFERINGS_DATABASE');
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
if (http_response_code() !== 200) throw new RuntimeException('The actual service-inquiry page did not return HTTP 200.');
foreach (['Your selected offers', 'Revenue Desk · 90-Day Programme', 'Continue as a monthly desk', 'name="name"', 'name="email"', 'name="privacy_consent"', 'name="message"', 'name="quote_currency"', 'name="quantities[x-sprint-flow]"', 'name="quantities[x-ent-team]"', 'max="1000"', 'workflow', 'quarter', 'action="/service-inquiry"'] as $expected) {
    if (!str_contains($html, $expected)) throw new RuntimeException('The connected inquiry form is missing expected context or field: '.$expected);
}
echo "Service inquiry form route render passed.\n";
