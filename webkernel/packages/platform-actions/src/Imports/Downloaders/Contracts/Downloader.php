<?php

namespace Webkernel\Platform\Actions\Imports\Downloaders\Contracts;

use Webkernel\Platform\Actions\Imports\Models\Import;
use Symfony\Component\HttpFoundation\Response;

interface Downloader
{
    public function __invoke(Import $import): Response;
}
