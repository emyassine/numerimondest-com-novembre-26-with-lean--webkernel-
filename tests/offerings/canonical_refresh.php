<?php declare(strict_types=1);

$base_path = dirname(__DIR__, 2);
require $base_path.'/internal/dependencies/autoload.php';

use Numerimondes\Offerings\Persistence\OfferingStore;
use App\Studio\Persistence\RefreshCanonicalOfferings;
use App\Studio\Persistence\RetireReplacedOfferings;

$seed = require $base_path.'/app/resources/data/studio/offerings.php';
$path = sys_get_temp_dir().'/nm-canonical-refresh-'.bin2hex(random_bytes(8)).'.sqlite';
try {
    OfferingStore::initialize($path);
    $store = new OfferingStore($path);
    $stale = $seed;
    foreach ($stale as &$offering) {
        $offering['price_display'] = 'Legacy display-only price';
        if (($offering['id'] ?? '') === 'mod-school') $offering['prices'] = array_values(array_filter($offering['prices'], static fn(array $price): bool => !str_ends_with($price['id'], '-onboarding')));
    }
    unset($offering);
    $store->saveDraft(['offerings' => $stale], 'catalog-seed');
    $first = $store->publish('catalog-seed');
    $second = RefreshCanonicalOfferings::apply($store, $seed);
    if ($second === null || $second <= $first) throw new RuntimeException('The guarded migration did not publish the final canonical seed.');
    $published = $store->current('published');
    $expected = RetireReplacedOfferings::retire(['offerings' => $seed]);
    if (json_encode($published['catalogue'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !== json_encode($expected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
        throw new RuntimeException('The refresh did not publish the canonical seed while retiring replaced offers.');
    }
    if (count($store->history()) !== 2 || RefreshCanonicalOfferings::apply($store, $seed) !== null) {
        throw new RuntimeException('The migration discarded history or was not idempotent.');
    }

    $adminPath = sys_get_temp_dir().'/nm-canonical-admin-'.bin2hex(random_bytes(8)).'.sqlite';
    OfferingStore::initialize($adminPath);
    $adminStore = new OfferingStore($adminPath);
    $adminStore->saveDraft(['offerings' => $seed], 'admin');
    $adminStore->publish('admin');
    try {
        RefreshCanonicalOfferings::apply($adminStore, $seed);
        throw new RuntimeException('The migration replaced a catalogue with administrator-authored history.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'non-seed actor')) throw $error;
    }
    foreach ([$adminPath, $adminPath.'-wal', $adminPath.'-shm'] as $file) if (is_file($file)) @unlink($file);

    $draftPath = sys_get_temp_dir().'/nm-canonical-draft-'.bin2hex(random_bytes(8)).'.sqlite';
    OfferingStore::initialize($draftPath);
    $draftStore = new OfferingStore($draftPath);
    $draftStore->saveDraft(['offerings' => $seed], 'catalog-seed');
    $draftStore->publish('catalog-seed');
    $draft = ['offerings' => $seed];
    $draft['offerings'][0]['name']['en'] .= ' draft';
    $draftStore->saveDraft($draft, 'catalog-seed');
    try {
        RefreshCanonicalOfferings::apply($draftStore, $seed);
        throw new RuntimeException('The migration replaced a divergent draft catalogue.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'divergent draft')) throw $error;
    }
    foreach ([$draftPath, $draftPath.'-wal', $draftPath.'-shm'] as $file) if (is_file($file)) @unlink($file);
    echo "Canonical refresh safety checks passed.\n";
} finally {
    foreach ([$path, $path.'-wal', $path.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
