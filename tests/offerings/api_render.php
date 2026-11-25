<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-api-render-'.bin2hex(random_bytes(6)).'.sqlite';
$registry = $root.'/config/partners.php';
if (is_file($registry)) throw new RuntimeException('Do not run API route test while deployment partner credentials are installed.');
$token = str_repeat('p', 48);
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
file_put_contents($registry, '<?php return '.var_export([[
    'id' => 'render-test', 'name' => 'Render test', 'enabled' => true,
    'secret_hash' => hash('sha256', $token), 'domains' => ['shop.example.test'],
    'offering_ids' => ['brand-system'],
]], true).';');
$_SERVER['REQUEST_URI'] = '/api/v1/offerings';
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
if (http_response_code() !== 200) throw new RuntimeException('Offering API route did not return HTTP 200.');
$payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
if (($payload['schema_version'] ?? null) !== 1 || count($payload['offerings'] ?? []) !== 1 || ($payload['offerings'][0]['id'] ?? null) !== 'brand-system') {
    throw new RuntimeException('Actual API route did not return the authenticated, partner-scoped published catalogue.');
}
echo "Partner API route render passed.\n";
