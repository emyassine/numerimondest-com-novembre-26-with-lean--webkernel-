<?php

namespace Webkernel\Platform\Widgets;

use Webkernel\Platform\Facades\Platform;

class AccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected string $view = 'webkernel-platform::widgets.account-widget';

    public static function canView(): bool
    {
        return Platform::auth()->check();
    }
}
