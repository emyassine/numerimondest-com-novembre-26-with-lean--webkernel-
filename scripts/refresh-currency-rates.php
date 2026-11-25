<?php declare(strict_types=1);

require dirname(__DIR__).'/internal/dependencies/autoload.php';

use App\Studio\Pricing\CurrencyRates;

$snapshot = CurrencyRates::refresh();
echo 'Updated '.count($snapshot['updated_currencies']).' reference currencies at '.$snapshot['refreshed_at_utc'].PHP_EOL;
