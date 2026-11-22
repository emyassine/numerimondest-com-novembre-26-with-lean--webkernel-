<?php

namespace Webkernel\Platform\Models\Contracts;

use Webkernel\Platform\Panel;
use Webkernel\ORM\Model;

interface HasDefaultTenant
{
    public function getDefaultTenant(Panel $panel): ?Model;
}
