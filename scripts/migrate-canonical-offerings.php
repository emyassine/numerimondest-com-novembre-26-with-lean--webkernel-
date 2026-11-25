<?php declare(strict_types=1);

require dirname(__DIR__).'/internal/dependencies/autoload.php';

use Numerimondes\Offerings\Persistence\OfferingStore;
use Numerimondes\Offerings\Persistence\QuoteStore;
use App\Studio\Catalog\Migration\RefreshCanonicalOfferings;

$arguments = array_values(array_filter(array_slice($argv, 1), static fn(string $value): bool => $value !== '--dry-run'));
$dryRun = in_array('--dry-run', $argv, true);
$database = $arguments[0] ?? OfferingStore::defaultPath();
$seed = require base_path('app/resources/data/studio/offerings.php');
$target = $database;
$temporary = null;

if ($dryRun) {
    $temporary = sys_get_temp_dir().'/nm-canonical-dry-run-'.bin2hex(random_bytes(8)).'.sqlite';
    if (is_file($database)) {
        $source = new PDO('sqlite:'.$database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $source->exec('PRAGMA wal_checkpoint(FULL)');
        $source->exec('VACUUM INTO '.$source->quote($temporary));
    }
    $target = $temporary;
} elseif (is_file($database)) {
    $backup = $database.'.pre-canonical-'.gmdate('Ymd\THis\Z').'.sqlite';
    $source = new PDO('sqlite:'.$database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $source->exec('PRAGMA wal_checkpoint(FULL)');
    $source->exec('VACUUM INTO '.$source->quote($backup));
    fwrite(STDOUT, 'SQLite backup created: '.$backup.PHP_EOL);
}

try {
    OfferingStore::initialize($target);
    QuoteStore::initialize($target);
    $revision = RefreshCanonicalOfferings::apply(new OfferingStore($target), $seed);
    if ($dryRun) {
        fwrite(STDOUT, $revision === null ? "Dry run: no migration required.\n" : "Dry run: canonical refresh would publish revision {$revision}.\n");
    } else {
        fwrite(STDOUT, $revision === null ? "Canonical catalogue is already current.\n" : "Published canonical catalogue revision {$revision}.\n");
    }
} finally {
    if (is_string($temporary)) foreach ([$temporary, $temporary.'-wal', $temporary.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
