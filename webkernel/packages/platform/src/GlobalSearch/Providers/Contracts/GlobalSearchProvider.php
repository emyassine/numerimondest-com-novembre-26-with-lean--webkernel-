<?php

namespace Webkernel\Platform\GlobalSearch\Providers\Contracts;

use Webkernel\Platform\GlobalSearch\GlobalSearchResults;

interface GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults;
}
