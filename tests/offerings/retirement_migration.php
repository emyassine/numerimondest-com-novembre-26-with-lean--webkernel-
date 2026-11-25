<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use Numerimondes\Offerings\Persistence\OfferingStore;
use App\Studio\Persistence\RetireReplacedOfferings;

$path = sys_get_temp_dir().'/nm-retire-'.bin2hex(random_bytes(6)).'.sqlite';
try {
    OfferingStore::initialize($path);
    $store = new OfferingStore($path);
    $store->saveDraft(['offerings' => [
        ['id' => 'brand-book', 'active' => true, 'visibility' => 'catalog', 'retained' => 'business data'],
        ['id' => 'sales-force', 'active' => true, 'visibility' => 'catalog'],
    ]], 'test');
    $store->publish('test');
    if (RetireReplacedOfferings::apply($store) === null) throw new RuntimeException('Replaced offers were not retired.');
    $rows = array_column($store->current('published')['catalogue']['offerings'], null, 'id');
    if (($rows['brand-book']['active'] ?? true) !== false || ($rows['brand-book']['visibility'] ?? '') !== 'hidden' || ($rows['brand-book']['retained'] ?? '') !== 'business data') {
        throw new RuntimeException('Retirement migration did not hide the replaced offer while preserving its record.');
    }
    if (($rows['sales-force']['active'] ?? false) !== true || RetireReplacedOfferings::apply($store) !== null) {
        throw new RuntimeException('Retirement migration changed a live offer or was not idempotent.');
    }
    echo "Retirement migration checks passed.\n";
} finally {
    foreach ([$path, $path.'-wal', $path.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
