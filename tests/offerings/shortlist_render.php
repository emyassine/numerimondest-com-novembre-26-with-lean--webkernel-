<?php declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-shortlist-render-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
$_COOKIE['nm_studio_service_shortlist'] = json_encode(['sales-desk', 'x-desk-mo', 'rec-ops'], JSON_THROW_ON_ERROR);
$_SERVER['REQUEST_URI'] = '/shortlist';
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
if (http_response_code() !== 200) throw new RuntimeException('The actual shortlist page did not return HTTP 200.');
$html = str_replace(["\u{202F}", "\u{00A0}"], ' ', $html);
if (!str_contains($html, 'Indicative total (one-time starting fees)')) throw new RuntimeException('The one-time shortlist total is missing.');
if (!preg_match('/59\s+000 MAD/', $html)) throw new RuntimeException('The shortlist total did not include the fixed fee while excluding monthly and percentage fees.');
if (!preg_match('/19\s+900 MAD \/ month/', $html) || !str_contains($html, '46% of monthly salary')) throw new RuntimeException('Excluded recurring or percentage fees are not shown alongside shortlist offerings.');
echo "Shortlist total route render passed.\n";
