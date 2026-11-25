<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;
use App\Studio\Catalog\OfferingType;

$offerings = require dirname(__DIR__, 2).'/app/resources/data/studio/offerings.php';
$byId = [];
foreach ($offerings as $offering) {
    if (!is_array($offering) || !is_string($offering['id'] ?? null) || isset($byId[$offering['id']])) {
        throw new RuntimeException('Seed catalogue contains a malformed or duplicate offering.');
    }
    $byId[$offering['id']] = $offering;
    if (OfferingType::tryFrom((string) ($offering['type'] ?? '')) === null) throw new RuntimeException('Unknown offering type: '.$offering['id']);
    foreach (($offering['industries'] ?? []) as $industry) if (Industry::tryFrom($industry) === null) throw new RuntimeException('Unknown industry on '.$offering['id'].': '.$industry);
    foreach (($offering['business_needs'] ?? []) as $need) if (BusinessNeed::tryFrom($need) === null) throw new RuntimeException('Unknown business need on '.$offering['id'].': '.$need);
    foreach (($offering['prices'] ?? []) as $component) {
        if (($component['percentage'] ?? null) !== null && !is_array($component['basis'] ?? null)) throw new RuntimeException('Percentage price has no basis on '.$offering['id']);
    }
}
$extensions = array_filter($byId, static fn (array $offering): bool => str_starts_with($offering['id'], 'x-'));
if (count($extensions) !== 20) throw new RuntimeException('Expected all 20 detail-only extensions.');
foreach ($extensions as $extension) {
    if (($extension['visibility'] ?? null) !== 'detail_only') throw new RuntimeException('Extension must be parent-detail-only: '.$extension['id']);
}
foreach (['mod-auto', 'mod-school'] as $id) {
    if (!in_array(Industry::EducationTraining->value, $byId[$id]['industries'] ?? [], true)) throw new RuntimeException('Education & Training is missing from '.$id);
}
$revenue = $byId['sales-force']['prices'] ?? [];
if (count($revenue) !== 3) throw new RuntimeException('Revenue Force must have setup, monthly supervision and recruitment fee components.');

echo 'Seed catalogue checks passed: '.count($byId).' offerings, '.count($extensions).' detail-only extensions. '.PHP_EOL;
