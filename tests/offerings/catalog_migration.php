<?php declare(strict_types=1);

$base_path = dirname(__DIR__, 2);
require $base_path.'/internal/dependencies/autoload.php';

use App\Studio\Catalog\OfferingRelationType;
use App\Studio\Admin\OfferingAdminController;
use App\Studio\Persistence\OfferingStore;
use Webkernel\Route\Request;

$seed = require $base_path.'/app/resources/data/studio/offerings.php';
$offerings = [];
foreach ($seed as $offer) $offerings[$offer['id']] = $offer;
$database = sys_get_temp_dir().'/nm-catalog-migration-'.bin2hex(random_bytes(8)).'.sqlite';
OfferingStore::initialize($database);
$store = new OfferingStore($database);
$store->saveDraft(['offerings' => $seed], 'seed-migration-check');
$revision = $store->publish('seed-migration-check');
$published = $store->current('published');
if (($published['revision'] ?? null) !== $revision || json_encode($published['catalogue']['offerings'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !== json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
    throw new RuntimeException('The canonical seed did not publish as an exact catalogue snapshot.');
}
$mandates = array_filter($offerings, static fn(array $offer): bool => ($offer['type'] ?? '') === 'mandate');
$extensions = array_filter($offerings, static fn(array $offer): bool => str_starts_with((string)($offer['id'] ?? ''), 'x-'));
if (count($offerings) !== 58 || count($mandates) !== 16 || count($extensions) !== 20) {
    throw new RuntimeException('The canonical seed catalogue does not contain the expected migrated offer set.');
}
foreach ($offerings as $id => $offer) {
    foreach (['name', 'description', 'type', 'prices', 'relations', 'industries', 'business_needs', 'visibility'] as $field) {
        if (!array_key_exists($field, $offer)) throw new RuntimeException("{$id} is missing canonical field {$field}.");
    }
    if (!is_array($offer['name']) || !isset($offer['name']['en'])) throw new RuntimeException("{$id} is missing its English name translation.");
    foreach (['price_display', 'price_amount', 'price_currency', 'price_interval', 'price_mad', 'price_text', 'price_lead', 'price_style', 'price_note', 'fee_percentage', 'monthly_min_mad', 'monthly_max_mad', 'billing', 'monthly', 'slug', 'engagement_type', 'card_description', 'related_slugs', 'cat', 'fmt', 'tier', 'onboarding_min_mad', 'onboarding_max_mad', 'href', 'brand', 'introduction', 'sections', 'class', 'featured', 'scope'] as $obsoleteField) {
        if (array_key_exists($obsoleteField, $offer)) throw new RuntimeException("{$id} retains obsolete canonical seed field {$obsoleteField}.");
    }
    foreach ($offer['relations'] as $relation) {
        if (OfferingRelationType::tryFrom((string)($relation['type'] ?? '')) === null || !isset($offerings[$relation['offering_id'] ?? ''])) {
            throw new RuntimeException("{$id} has an invalid relation or relation target.");
        }
    }
}
$extensionLinks = 0;
foreach ($mandates as $mandate) {
    foreach ($mandate['relations'] as $relation) {
        if (($relation['type'] ?? '') === 'extension' && ($relation['visibility'] ?? '') === 'detail_only') $extensionLinks++;
    }
}
if ($extensionLinks !== 20) throw new RuntimeException('Detail-only extension relations were not preserved.');
$expectedExtensionPrices = [
    'x-brand-kit' => [12000, 'one_time'], 'x-brand-multi' => [30000, 'one_time'],
    'x-flag-bi' => [12000, 'one_time'], 'x-flag-lead' => [9000, 'one_time'],
    'x-cocon-10' => [25000, 'one_time'], 'x-cocon-ml' => [40000, 'one_time'], 'x-cocon-links' => [35000, 'one_time'],
    'x-multi-mkt' => [90000, 'one_time'], 'x-multi-gov' => [60000, 'one_time'],
    'x-sprint-flow' => [9000, 'one_time'], 'x-sprint-train' => [6000, 'one_time'],
    'x-crm-int' => [12000, 'one_time'], 'x-crm-app' => [55000, 'one_time'],
    'x-saas-admin' => [45000, 'one_time'], 'x-saas-pay' => [30000, 'one_time'], 'x-saas-sec' => [25000, 'one_time'],
    'x-ent-team' => [150000, 'quarterly'], 'x-study-geo' => [30000, 'one_time'],
    'x-desk-seg' => [20000, 'one_time'], 'x-desk-mo' => [19900, 'monthly'],
];
foreach ($expectedExtensionPrices as $id => [$amount, $billing]) {
    $component = $offerings[$id]['prices'][0] ?? [];
    if ((float)($component['amount_mad'] ?? -1) !== (float)$amount || ($component['billing'] ?? null) !== $billing) {
        throw new RuntimeException("The supplied extension price or billing model was not preserved for {$id}.");
    }
}
$deskExtension = $offerings['x-desk-mo'] ?? [];
foreach ($mandates as $mandate) {
    foreach ($mandate['relations'] as $relation) {
        if (($relation['offering_id'] ?? null) === 'x-desk-mo' && ($relation['type'] ?? null) === 'extension') $deskParent = $mandate['id'];
    }
}
if (isset($deskExtension['parent'], $deskExtension['parent_name']) || ($deskParent ?? null) !== 'sales-desk') {
    throw new RuntimeException('Extension parentage is duplicated on the child or not discoverable from its relation.');
}
$recruitment = $offerings['rec-ops']['prices'][0] ?? null;
if ((float)($recruitment['percentage'] ?? 0) !== 46.0 || ($recruitment['basis']['kind'] ?? null) !== 'monthly_salary') {
    throw new RuntimeException('Recruitment percentage pricing or its calculation basis was lost.');
}
if (($offerings['mod-school']['industries'] ?? []) !== ['education-training']) {
    throw new RuntimeException('Education & Training classification was not retained for the school module.');
}
$onboarding = array_values(array_filter($offerings['mod-school']['prices'], static fn(array $price): bool => ($price['id'] ?? '') === 'mod-school-onboarding'))[0] ?? null;
if (($onboarding['minimum_mad'] ?? null) !== 1500 || ($onboarding['maximum_mad'] ?? null) !== 3000 || ($onboarding['billing'] ?? null) !== 'one_time') {
    throw new RuntimeException('Software onboarding fees are not stored as canonical price components.');
}
if (($offerings['automation-sprint']['summary']['en'] ?? null) !== 'Automate three to five repeatable workflows across your existing tools, with clear handover for your team.') {
    throw new RuntimeException('The retired offering card copy was not preserved in the canonical summary field.');
}
foreach (['Choose repeatable steps that consume time', 'Typical workflow scope', 'Three to five workflows agreed for the sprint.'] as $retainedCopy) {
    if (!str_contains($offerings['automation-sprint']['description']['en'] ?? '', $retainedCopy)) throw new RuntimeException('Retired detail copy was not preserved in canonical description content: '.$retainedCopy);
}
$unitSpecs = ['x-sprint-flow' => ['workflow', 'one_time'], 'x-ent-team' => ['quarter', 'quarterly']];
foreach ($unitSpecs as $id => [$unit, $billing]) {
    $price = $offerings[$id]['prices'][0] ?? [];
    if (($price['unit'] ?? null) !== $unit || ($price['billing'] ?? null) !== $billing) throw new RuntimeException("{$id} is missing its canonical unit and billing metadata.");
}
foreach ($mandates as $mandate) {
    if (($mandate['active'] ?? false) === true) {
        if (!is_string($mandate['practice'] ?? null) || !is_string($mandate['image_source'] ?? null) || !is_file($base_path.'/app/resources/views/pages/'.$mandate['image_source'])) {
            throw new RuntimeException(($mandate['id'] ?? 'Unknown'). ' does not carry a valid practice and image reference directly.');
        }
    }
}
if (class_exists(App\Studio\Catalog\ServiceCatalog::class) || class_exists(App\Studio\Catalog\MandateCatalog::class)) {
    throw new RuntimeException('An obsolete runtime catalog class remains available.');
}
putenv('NM_OFFERINGS_ADMIN_TOKEN='.str_repeat('m', 48));
$preview = OfferingAdminController::preview(new Request(
    'POST', '/admin/offerings/preview',
    headers: ['authorization' => 'Bearer '.str_repeat('m', 48)],
    content: json_encode(['offerings' => array_values($offerings)], JSON_THROW_ON_ERROR),
));
putenv('NM_OFFERINGS_ADMIN_TOKEN');
if ($preview->getStatusCode() !== 200) throw new RuntimeException('The full migrated seed catalogue fails administration validation: '.$preview->getContent());
foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) unlink($file);
echo "Canonical catalog migration passed: 58 records, 16 mandates, 20 extension links.\n";
