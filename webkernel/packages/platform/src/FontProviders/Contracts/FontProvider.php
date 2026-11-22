<?php

namespace Webkernel\Platform\FontProviders\Contracts;

use Webkernel\View\Htmlable;

interface FontProvider
{
    public function getHtml(string $family, ?string $url = null): Htmlable;
}
