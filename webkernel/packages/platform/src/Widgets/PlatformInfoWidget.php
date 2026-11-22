<?php

namespace Webkernel\Platform\Widgets;

class PlatformInfoWidget extends Widget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected string $view = 'webkernel-platform::widgets.platform-info-widget';
}
