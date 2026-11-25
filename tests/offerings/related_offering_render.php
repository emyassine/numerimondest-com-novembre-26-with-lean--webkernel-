<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
ini_set('session.save_path', sys_get_temp_dir());
$database = sys_get_temp_dir().'/nm-related-offering-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require $root.'/internal/dependencies/autoload.php';
App\Studio\Persistence\OfferingStore::initialize($database);
App\Studio\Persistence\QuoteStore::initialize($database);
$store = new App\Studio\Persistence\OfferingStore($database);
$store->saveDraft(['offerings' => [
    [
        'id' => 'parent-mandate', 'type' => 'mandate', 'name' => ['en' => 'Parent mandate'],
        'description' => ['en' => 'A parent scope.'], 'summary' => ['en' => 'Parent summary.'],
        'category' => 'Business systems', 'format' => 'Project', 'engagement' => 'Project',
        'industries' => ['technology'], 'business_needs' => ['operations'], 'active' => true,
        'visibility' => 'catalog', 'prices' => [], 'relations' => [[
            'offering_id' => 'maintenance-plan', 'type' => 'maintenance', 'optional' => true, 'visibility' => 'detail_only',
        ]],
    ],
    [
        'id' => 'maintenance-plan', 'type' => 'service', 'name' => ['en' => 'Maintenance plan'],
        'description' => ['en' => 'Ongoing system maintenance.'], 'category' => 'Maintenance',
        'format' => 'Follow-up', 'engagement' => 'Ongoing', 'industries' => ['technology'],
        'business_needs' => ['operations'], 'active' => true, 'visibility' => 'detail_only',
        'prices' => [['id' => 'maintenance-monthly', 'amount_mad' => 1000, 'billing' => 'monthly']], 'relations' => [],
    ],
]], 'test');
$store->publish('test');
$_SERVER['REQUEST_URI'] = '/services/maintenance-plan';
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
if (http_response_code() !== 200 || !str_contains($html, 'Maintenance plan') || !str_contains($html, 'Ongoing system maintenance.')) {
    throw new RuntimeException('A related detail-only maintenance offering did not resolve through its routed detail page. HTTP '.http_response_code().' '.substr(strip_tags($html), 0, 180));
}
echo "Related offering detail route passed.\n";
