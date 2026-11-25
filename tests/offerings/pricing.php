<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

use App\Studio\Catalog\Offering;
use App\Studio\Catalog\OfferingType;
use App\Studio\Catalog\PriceComponent;
use App\Studio\Pricing\OfferingPriceResolver;
use App\Studio\Pricing\PartnerPriceRule;
use App\Studio\Pricing\CurrencyRates;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.' Expected '.var_export($expected, true).', got '.var_export($actual, true));
    }
}

$recruitment = new Offering(
    'recruitment', OfferingType::Service, ['en' => 'Recruitment'], ['en' => ''], [], [], [
        new PriceComponent('recruitment-fee', null, 'percentage', percentage: 46.0, basis: ['value_key' => 'monthly_salary_mad']),
    ],
);
$resolver = new OfferingPriceResolver();
$rates = ['MAD' => 1.0, 'EUR' => 0.1];
$configuredRates = CurrencyRates::forPricing();
if (($configuredRates['rates']['MAD'] ?? null) !== 1.0 || !isset($configuredRates['rates']['USD'], $configuredRates['rates']['EUR'])) {
    throw new RuntimeException('The currency resolver did not load the application reference rates.');
}

$undisclosed = $resolver->resolve($recruitment, ratesPerMad: $rates)[0];
assertSameValue(null, $undisclosed['amount'], 'A percentage fee without its salary basis must not invent a fixed amount.');
assertSameValue(46.0, $undisclosed['percentage'], 'Percentage terms remain explicit.');

$priced = $resolver->resolve($recruitment, 'EUR', basisValues: ['monthly_salary_mad' => 20000], basisCurrencies: ['monthly_salary_mad' => 'MAD'], ratesPerMad: $rates)[0];
assertSameValue(20000.0, $priced['basis_amount'], 'The basis amount is preserved in MAD.');
assertSameValue(920.0, $priced['amount'], 'Percentage amount is computed in MAD before currency conversion.');
$foreignBasis = $resolver->resolve($recruitment, 'EUR', basisValues: ['monthly_salary_mad' => 20000], basisCurrencies: ['monthly_salary_mad' => 'EUR'], ratesPerMad: $rates)[0];
assertSameValue(9200.0, $foreignBasis['amount'], 'Foreign salary bases are converted to MAD before applying percentage pricing.');

$fixed = new Offering(
    'fixed-service', OfferingType::Service, ['en' => 'Fixed'], ['en' => ''], [], [],
    [new PriceComponent('fixed-fee', 10000)],
);
$rule = new PartnerPriceRule('partner-a', 'fixed-service', 'adjust_percent', adjustmentPercent: 10);
$partner = $resolver->resolve($fixed, 'EUR', $rule, ratesPerMad: $rates)[0];
assertSameValue(1100.0, $partner['amount'], 'Partner pricing is applied to the MAD catalog price before FX conversion.');

$ignored = $resolver->resolve($fixed, 'EUR', new PartnerPriceRule('partner-a', 'fixed-service', 'adjust_percent', adjustmentPercent: 10, startsAt: '2099-01-01'), new DateTimeImmutable('2026-01-01'), ratesPerMad: $rates)[0];
assertSameValue(1000.0, $ignored['amount'], 'Future partner rules are not applied early.');

$invalidRejected = false;
try {
    new PriceComponent('bad-percentage', null, 'percentage', percentage: 46.0);
} catch (InvalidArgumentException) {
    $invalidRejected = true;
}
assertSameValue(true, $invalidRejected, 'A percentage component without a calculation basis is rejected.');
$unitOffering = Offering::fromArray([
    'id' => 'workflow-unit', 'type' => 'service', 'name' => ['en' => 'Workflow'], 'description' => ['en' => ''],
    'prices' => [['id' => 'workflow-fee', 'amount_mad' => 9000, 'billing' => 'one_time', 'unit' => 'workflow']],
]);
$unitOne = $resolver->resolve($unitOffering, ratesPerMad: $rates, quantity: 1)[0];
assertSameValue(9000.0, $unitOne['amount'], 'One workflow must cost one unit price.');
assertSameValue(9000.0, $unitOne['unit_amount'], 'Unit price must remain visible in the resolved component.');
assertSameValue('workflow', $unitOne['unit'], 'Resolved unit metadata was lost.');
$unitThree = $resolver->resolve($unitOffering, ratesPerMad: $rates, quantity: 3)[0];
assertSameValue(27000.0, $unitThree['amount'], 'Three workflows must multiply the MAD unit price.');
assertSameValue(3, $unitThree['quantity'], 'Resolved unit quantity was not preserved.');
$fractionalUnit = Offering::fromArray([
    'id' => 'fractional-unit', 'type' => 'service', 'name' => ['en' => 'Fractional'], 'description' => ['en' => ''],
    'prices' => [['id' => 'fractional-fee', 'amount_mad' => 1, 'billing' => 'one_time', 'unit' => 'item']],
]);
$fractionalTotal = $resolver->resolve($fractionalUnit, 'EUR', ratesPerMad: ['MAD' => 1.0, 'EUR' => 0.33333], quantity: 3)[0];
assertSameValue(1.0, $fractionalTotal['amount'], 'Quantity must be extended in MAD before the final currency conversion and rounding.');
assertSameValue(0.33, $fractionalTotal['unit_amount'], 'The quote must separately preserve the rounded display unit price.');
$unitPartner = $resolver->resolve($unitOffering, rule: new PartnerPriceRule('partner-a', 'workflow-unit', 'adjust_percent', adjustmentPercent: 10), ratesPerMad: $rates, quantity: 3)[0];
assertSameValue(29700.0, $unitPartner['amount'], 'Partner adjustments must apply per unit before multiplying quantity.');
foreach ([0, 1001] as $invalidQuantity) {
    $rejected = false;
    try { $resolver->resolve($unitOffering, ratesPerMad: $rates, quantity: $invalidQuantity); } catch (InvalidArgumentException) { $rejected = true; }
    assertSameValue(true, $rejected, 'Out-of-range unit quantities must be rejected.');
}
$nonUnitRejected = false;
try { $resolver->resolve($fixed, ratesPerMad: $rates, quantity: 2); } catch (InvalidArgumentException) { $nonUnitRejected = true; }
assertSameValue(true, $nonUnitRejected, 'Quantity must be rejected for offerings with no unit-priced component.');
assertSameValue(100.0, OfferingPriceResolver::oneTimeStartingTotal([
    ['billing' => 'one_time', 'amount' => 100],
    ['billing' => 'monthly', 'amount' => 200],
    ['billing' => 'percentage', 'amount' => 300, 'percentage' => 46],
]), 'Indicative totals include one-time fixed fees only.');

echo "Pricing checks passed.\n";
