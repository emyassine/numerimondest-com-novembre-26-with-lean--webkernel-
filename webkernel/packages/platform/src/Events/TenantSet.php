<?php

namespace Webkernel\Platform\Events;

use Webkernel\Platform\Models\Contracts\HasTenants;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\ORM\Model;
use \Webkernel\Events\Dispatchable;

class TenantSet
{
    use Dispatchable;

    public function __construct(
        protected Model $tenant,
        protected Model | Authenticatable | HasTenants $user,
    ) {}

    public function getTenant(): Model
    {
        return $this->tenant;
    }

    public function getUser(): Model | Authenticatable | HasTenants
    {
        return $this->user;
    }
}
