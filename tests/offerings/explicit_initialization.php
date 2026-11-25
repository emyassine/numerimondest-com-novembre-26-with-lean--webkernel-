<?php declare(strict_types=1);

use App\Studio\Catalog\OfferingCatalog;
use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;
use App\Studio\Persistence\RefreshCanonicalOfferings;

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-explicit-catalogue-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require $root.'/internal/dependencies/autoload.php';

try {
    try {
        OfferingCatalog::all();
        throw new RuntimeException('A public catalogue read silently initialized a new database.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'scripts/migrate-offerings-database.php')) throw $error;
    }
    if (is_file($database)) throw new RuntimeException('A catalogue read created a database file.');
    OfferingStore::initialize();
    QuoteStore::initialize();
    $store = new OfferingStore();
    if ($store->current('published') !== null) throw new RuntimeException('A fresh database unexpectedly contained a publication.');
    try {
        OfferingCatalog::all();
        throw new RuntimeException('A public catalogue read silently initialized a new database.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'scripts/migrate-canonical-offerings.php')) throw $error;
    }
    if ($store->current('published') !== null) throw new RuntimeException('A failed catalogue read wrote a publication.');
    $seed = require $root.'/app/resources/data/studio/offerings.php';
    RefreshCanonicalOfferings::apply($store, $seed);
    if (count(OfferingCatalog::all()) !== count($seed)) throw new RuntimeException('Explicit catalogue initialization did not publish the canonical records.');
    echo "Explicit catalogue initialization checks passed.\n";
} finally {
    putenv('NM_OFFERINGS_DATABASE');
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
