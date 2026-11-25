<?php declare(strict_types=1);

use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;

/** Initializes an isolated test database explicitly, as deployment initialization does. */
function initializeTestCatalogue(string $projectRoot): void
{
    require_once $projectRoot.'/internal/dependencies/autoload.php';
    OfferingStore::initialize();
    QuoteStore::initialize();
    $store = new OfferingStore();
    if ($store->current('published') !== null) return;
    $seed = require $projectRoot.'/app/resources/data/studio/offerings.php';
    if (!is_array($seed) || $seed === []) throw new RuntimeException('Canonical test catalogue is missing.');
    $store->saveDraft(['offerings' => $seed], 'test-initialization');
    $store->publish('test-initialization');
}
