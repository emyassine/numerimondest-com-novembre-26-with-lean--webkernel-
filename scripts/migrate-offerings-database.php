<?php declare(strict_types=1);

require dirname(__DIR__).'/internal/dependencies/autoload.php';

use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;

$path = $argv[1] ?? OfferingStore::defaultPath();
OfferingStore::initialize($path);
QuoteStore::initialize($path);
fwrite(STDOUT, 'Offerings and quote schema is ready at '.$path.PHP_EOL);
