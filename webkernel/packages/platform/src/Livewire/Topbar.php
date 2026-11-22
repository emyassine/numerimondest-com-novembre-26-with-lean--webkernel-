<?php

namespace Webkernel\Platform\Livewire;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Livewire\Concerns\HasTenantMenu;
use Webkernel\Platform\Livewire\Concerns\HasUserMenu;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\Liveview\Attributes\On;
use Webkernel\Liveview\Component;

class Topbar extends Component implements HasActions, HasSchemas
{
    use HasTenantMenu;
    use HasUserMenu;
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[On('refresh-topbar')]
    public function refresh(): void {}

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-platform::livewire.topbar');
    }
}
