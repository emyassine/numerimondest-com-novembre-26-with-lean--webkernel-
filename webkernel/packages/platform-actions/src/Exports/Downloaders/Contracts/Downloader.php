<?php

namespace Webkernel\Platform\Actions\Exports\Downloaders\Contracts;

use Webkernel\Platform\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\Response;

interface Downloader
{
    public function __invoke(Export $export): Response;
}
