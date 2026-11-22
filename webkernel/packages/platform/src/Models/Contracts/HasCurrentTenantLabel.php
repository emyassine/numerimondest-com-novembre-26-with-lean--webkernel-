<?php

namespace Webkernel\Platform\Models\Contracts;

interface HasCurrentTenantLabel
{
    public function getCurrentTenantLabel(): string;
}
