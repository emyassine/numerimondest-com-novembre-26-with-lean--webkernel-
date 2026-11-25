<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('BASEPATH', true);
define('FCPATH', $root.'/account/');
require $root.'/packages/numerimondes-offerings/src/Persistence/OfferingStore.php';
require $root.'/account/modules/nm_offerings/libraries/Nm_offerings_catalog.php';

use Numerimondes\Offerings\Persistence\OfferingStore;

$path = sys_get_temp_dir().'/nm-perfex-offerings-'.bin2hex(random_bytes(8)).'.sqlite';
OfferingStore::initialize($path);
$catalogue = [
    'offerings' => [
        ['id' => 'public-offer', 'type' => 'service', 'name' => ['en' => 'Public'], 'description' => ['en' => ''], 'industries' => ['education-training'], 'business_needs' => ['operations'], 'prices' => [], 'relations' => [], 'active' => true, 'visibility' => 'catalog'],
        ['id' => 'detail-offer', 'type' => 'service', 'name' => ['en' => 'Detail'], 'description' => ['en' => ''], 'industries' => ['education-training'], 'business_needs' => ['operations'], 'prices' => [], 'relations' => [], 'active' => true, 'visibility' => 'detail_only'],
        ['id' => 'hidden-offer', 'type' => 'service', 'name' => ['en' => 'Hidden'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [], 'relations' => [], 'active' => false, 'visibility' => 'hidden'],
    ],
];
$catalog = new Nm_offerings_catalog($path, $root);
$draft = $catalog->saveDraft($catalogue, 7);
if ($draft < 1) throw new RuntimeException('Perfex module did not save the catalogue draft.');
$published = $catalog->publish(7);
if ($published <= $draft) throw new RuntimeException('Perfex module did not publish a new revision.');
if (($catalog->store()->history()[0]['created_by'] ?? null) !== 'perfex-staff:7') {
    throw new RuntimeException('Perfex publication history did not retain the staff actor.');
}
if (count($catalog->all()) !== 1 || ($catalog->all()[0]['id'] ?? null) !== 'public-offer') {
    throw new RuntimeException('Perfex helper-facing catalog listing exposed detail-only or hidden offerings.');
}
if (count($catalog->all(true)) !== 2 || $catalog->find('detail-offer') === null || $catalog->find('hidden-offer') !== null) {
    throw new RuntimeException('Perfex detail lookup visibility rules are incorrect.');
}
$invalid = $catalogue;
$invalid['offerings'][0]['business_needs'] = ['not-a-real-need'];
try {
    $catalog->saveDraft($invalid, 7);
    throw new RuntimeException('Perfex module accepted an invalid business-need classification.');
} catch (InvalidArgumentException $expected) {
}
$record = ['id' => 'new-offer', 'type' => 'service', 'name' => ['en' => 'New'], 'description' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [], 'relations' => [], 'active' => true, 'visibility' => 'catalog'];
$catalog->saveOffering($record, '', 7);
$record['name']['en'] = 'Updated';
$catalog->saveOffering($record, 'new-offer', 7);
$working = $catalog->workingCatalogue()['catalogue']['offerings'];
if (count($working) !== 4 || ($catalog->find('public-offer')['id'] ?? null) !== 'public-offer') {
    throw new RuntimeException('Perfex record create/edit did not preserve the other offerings and published lookup.');
}
try {
    $record['id'] = 'renamed-offer';
    $catalog->saveOffering($record, 'new-offer', 7);
    throw new RuntimeException('Perfex editor allowed a stable offering ID to change.');
} catch (InvalidArgumentException $expected) {
}
foreach ([$path, $path.'-wal', $path.'-shm'] as $file) if (is_file($file)) unlink($file);
echo "Perfex module catalogue access, draft, publication and visibility checks passed.\n";
