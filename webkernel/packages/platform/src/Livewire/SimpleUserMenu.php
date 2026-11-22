<?php

namespace Webkernel\Platform\Livewire;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Livewire\Concerns\HasUserMenu;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\Liveview\Component;

class SimpleUserMenu extends Component implements HasActions, HasSchemas
{
    use HasUserMenu;
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-platform::livewire.simple-user-menu');
    }
}
