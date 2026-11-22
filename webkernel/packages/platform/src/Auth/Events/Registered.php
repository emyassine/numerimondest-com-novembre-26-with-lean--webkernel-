<?php

namespace Webkernel\Platform\Auth\Events;

use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Queue\SerializesModels;

class Registered
{
    use SerializesModels;

    public function __construct(
        protected Authenticatable $user,
    ) {}

    public function getUser(): Authenticatable
    {
        return $this->user;
    }
}
