<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use Numerimondes\Offerings\Persistence\QuoteStore;
use Numerimondes\Offerings\Pricing\QuoteSnapshot;

$path = sys_get_temp_dir().'/nm-quotes-'.bin2hex(random_bytes(6)).'.sqlite';
try {
    QuoteStore::initialize($path);
    $store = new QuoteStore($path);
    $created = new DateTimeImmutable('2026-11-24T10:00:00+00:00');
    $quote = new QuoteSnapshot('quote-a', 'partner-a', '41', 'pricing-7', 'MAD', [
        ['offering_id' => 'sales-desk', 'price_mad' => 45000, 'quantity' => 1, 'unit' => null, 'parent_id' => null],
        ['offering_id' => 'x-sprint-flow', 'price_mad' => 27000, 'quantity' => 3, 'unit' => 'workflow', 'parent_id' => 'sys-sprint'],
    ], $created, $created->modify('+30 days'));
    $store->save($quote);
    $restored = $store->find('quote-a');
    if ($restored === null || $restored->toArray() !== $quote->toArray()) throw new RuntimeException('Stored quote snapshot did not round-trip exactly.');
    $immutable = false;
    try { $store->save($quote); } catch (PDOException) { $immutable = true; }
    if (!$immutable) throw new RuntimeException('Duplicate quote ID overwrote an immutable quote snapshot.');
    if ($store->find('missing') !== null) throw new RuntimeException('Missing quote lookup should return null.');
    $invalidQuantity = false;
    try {
        new QuoteSnapshot('quote-bad-quantity', 'partner-a', '41', 'pricing-7', 'MAD', [['offering_id' => 'x-sprint-flow', 'quantity' => 1001, 'unit' => 'workflow']], $created, $created->modify('+30 days'));
    } catch (InvalidArgumentException) { $invalidQuantity = true; }
    if (!$invalidQuantity) throw new RuntimeException('Quote snapshots must reject out-of-range unit quantities.');
    echo "Quote snapshot checks passed.\n";
} finally {
    foreach ([$path, $path.'-wal', $path.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
