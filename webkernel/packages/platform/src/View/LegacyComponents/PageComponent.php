<?php

namespace Webkernel\Platform\View\LegacyComponents;

use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use \Webkernel\View\Component;

class PageComponent extends Component
{
    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-platform::components.page.index');
    }
}
