<?php

namespace Webkernel\Platform\Support\Contracts;

use Webkernel\Platform\Support\View\ComponentAttributeBag;

interface LoadingIndicator
{
    public function toHtml(ComponentAttributeBag $attributes): string;
}
