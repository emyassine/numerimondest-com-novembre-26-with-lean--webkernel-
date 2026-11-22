<?php

namespace Webkernel\Platform\Models\Contracts;

use Webkernel\Platform\Panel;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Collection;

interface HasTenants
{
    public function canAccessTenant(Model $tenant): bool;

    /**
     * @return array<Model> | Collection
     */
    public function getTenants(Panel $panel): array | Collection;
}
