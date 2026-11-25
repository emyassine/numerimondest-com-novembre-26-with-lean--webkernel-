<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-admin-render-'.bin2hex(random_bytes(6)).'.sqlite';
$token = str_repeat('a', 48);
putenv('NM_OFFERINGS_DATABASE='.$database);
putenv('NM_OFFERINGS_ADMIN_TOKEN='.$token);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
$_SERVER['REQUEST_URI'] = '/admin/offerings';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.base64_encode('admin:'.$token);
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
try { require $root.'/public/index.php'; }
finally {
    $html = (string) ob_get_clean();
    putenv('NM_OFFERINGS_DATABASE');
    putenv('NM_OFFERINGS_ADMIN_TOKEN');
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
if (http_response_code() !== 200 || !str_contains($html, 'Offerings catalogue') || !str_contains($html, 'Validate preview') || !str_contains($html, 'Publish draft')) {
    throw new RuntimeException('Authenticated Webkernel administration route did not render its editor workflow.');
}
echo "Admin route render passed.\n";
