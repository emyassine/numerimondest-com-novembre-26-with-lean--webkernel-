<?php

namespace Webkernel\Platform\Support\Components\Contracts;

interface HasEmbeddedView
{
    public function toEmbeddedHtml(): string;
}
