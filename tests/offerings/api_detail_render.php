<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-api-detail-render-'.bin2hex(random_bytes(6)).'.sqlite';
$registry = $root.'/config/partners.php';
if (is_file($registry)) throw new RuntimeException('Do not run API route test while deployment partner credentials are installed.');
$token = str_repeat('d', 48);
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
file_put_contents($registry, '<?php return '.var_export([[
    'id' => 'detail-render-test', 'name' => 'Detail render test', 'enabled' => true,
    'secret_hash' => hash('sha256', $token), 'domains' => ['shop.example.test'],
    'offering_ids' => ['brand-system'],
]], true).';');
$_SERVER['REQUEST_URI'] = '/api/v1/offerings/brand-system';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTP_ORIGIN'] = 'https://shop.example.test';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
try { require $root.'/public/index.php'; }
finally {
    $body = (string) ob_get_clean();
    @unlink($registry);
    putenv('NM_OFFERINGS_DATABASE');
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
if (http_response_code() !== 200) throw new RuntimeException('Offering detail API route did not return HTTP 200.');
$payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
if (($payload['schema_version'] ?? null) !== 1 || ($payload['offering']['id'] ?? null) !== 'brand-system' || isset($payload['offerings'])) {
    throw new RuntimeException('Actual offering detail route did not return only the authorized offering.');
}
echo "Partner offering detail route render passed.\n";
