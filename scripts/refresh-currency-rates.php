#!/usr/bin/env php
<?php declare(strict_types=1);

require dirname(__DIR__).'/internal/dependencies/autoload.php';

try {
    $snapshot = \App\Studio\CurrencyRates::refresh();
    fwrite(STDOUT, 'Updated '.count($snapshot['updated_currencies']).' reference currencies; rate date '.implode(', ', array_unique(array_intersect_key($snapshot['rate_dates'], array_flip($snapshot['updated_currencies'])))).PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Currency rate refresh failed: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
