<?php

namespace Webkernel\Platform\Notifications\Concerns;

use BackedEnum;
use Webkernel\Platform\Notifications\View\NotificationsIconAlias;
use Webkernel\Platform\Support\Concerns\HasIcon as BaseTrait;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\View\Htmlable;

trait HasIcon
{
    use BaseTrait {
        getIcon as getBaseIcon;
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return $this->getBaseIcon() ?? match ($this->getStatus()) {
            'danger' => PlatformIcon::resolve(NotificationsIconAlias::NOTIFICATION_DANGER) ?? Heroicon::OutlinedXCircle,
            'info' => PlatformIcon::resolve(NotificationsIconAlias::NOTIFICATION_INFO) ?? Heroicon::OutlinedInformationCircle,
            'success' => PlatformIcon::resolve(NotificationsIconAlias::NOTIFICATION_SUCCESS) ?? Heroicon::OutlinedCheckCircle,
            'warning' => PlatformIcon::resolve(NotificationsIconAlias::NOTIFICATION_WARNING) ?? Heroicon::OutlinedExclamationCircle,
            default => null,
        };
    }
}
