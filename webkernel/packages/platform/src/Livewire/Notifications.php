<?php

namespace Webkernel\Platform\Livewire;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Notifications\Livewire\Notifications as BaseComponent;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\ORM\Model;

class Notifications extends BaseComponent
{
    public function getUser(): Model | Authenticatable | null
    {
        return Platform::auth()->user();
    }
}
