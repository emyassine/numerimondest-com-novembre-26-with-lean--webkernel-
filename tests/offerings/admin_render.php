<?php declare(strict_types=1);

use Webkernel\Session\Session;

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-admin-render-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);
require $root.'/internal/dependencies/autoload.php';
$sessionPath = $root.'/runtime/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
Session::configure(['cookie' => 'NMAdminRouteTest', 'save_path' => $sessionPath, 'lifetime' => 120, 'same_site' => 'Lax', 'http_only' => true, 'secure' => false]);
$secretPath = $root.'/config/offerings-admin.php';
$hadSecret = is_file($secretPath);
if (!$hadSecret) file_put_contents($secretPath, "<?php return ".var_export(['username' => 'test-admin', 'password_hash' => password_hash('test-password-for-suite', PASSWORD_DEFAULT)], true).";\n");
$_SERVER['REQUEST_URI'] = '/admin/login';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
try { require $root.'/public/index.php'; }
finally {
    $html = (string) ob_get_clean();
    putenv('NM_OFFERINGS_DATABASE');
    if (!$hadSecret) unlink($secretPath);
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
if (http_response_code() !== 200 || !str_contains($html, 'Sign in') || !str_contains($html, 'autocomplete="current-password"') || !str_contains($html, 'name="_csrf"')
    || !str_contains($html, 'dependable-ui.themes.css') || !str_contains($html, 'fi-section')) {
    throw new RuntimeException('Admin login route did not render its styled Webkernel Filament login form.');
}
echo "Admin login route and Filament styling render passed.\n";
