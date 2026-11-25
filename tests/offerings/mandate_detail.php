<?php declare(strict_types=1);

use App\Studio\Http\ServiceMandateController;

ini_set('session.save_path', sys_get_temp_dir());
$database = sys_get_temp_dir().'/nm-mandate-detail-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue(dirname(__DIR__, 2));
$_SERVER['REQUEST_URI'] = '/services/dig-cocon';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
require dirname(__DIR__, 2).'/public/index.php';
$html = (string)ob_get_clean();
if (http_response_code() !== 200) throw new RuntimeException('The mandate detail route did not return HTTP 200.');
foreach (['Search-Engineered Commercial Asset', '10-page expert cluster', 'Multilingual pack', 'Authority link-building programme'] as $copy) {
    if (!str_contains($html, $copy)) throw new RuntimeException('The mandate detail omitted canonical offering content: '.$copy);
}
foreach (['x-cocon-10', 'x-cocon-ml', 'x-cocon-links'] as $extensionId) {
    if (!preg_match('/name="service" value="'.preg_quote($extensionId, '/').'"/', $html)) {
        throw new RuntimeException('The extension shortlist action is not wired for '.$extensionId.'.');
    }
}
if (preg_match('/Fatal error|Warning:|Undefined variable|ParseError/', $html)) throw new RuntimeException('The mandate detail rendered an application error.');
if (ServiceMandateController::show('x-cocon-10')->getStatusCode() !== 404) throw new RuntimeException('An extension must not have a standalone public detail page.');
echo 'Mandate detail render passed: '.strlen($html).' bytes.'.PHP_EOL;
putenv('NM_OFFERINGS_DATABASE');
foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
