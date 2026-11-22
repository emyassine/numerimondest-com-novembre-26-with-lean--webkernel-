<?php

namespace Webkernel\Platform\Notifications\Testing;

use Closure;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Liveview\Component;
use Webkernel\Liveview\Features\SupportTesting\Testable;

/**
 * @method Component instance()
 *
 * @mixin Testable
 */
class TestsNotifications
{
    public function assertNotified(): Closure
    {
        return function (Notification | string | null $notification = null): static {
            Notification::assertNotified($notification);

            return $this;
        };
    }

    public function assertNotNotified(): Closure
    {
        return function (Notification | string | null $notification = null): static {
            Notification::assertNotNotified($notification);

            return $this;
        };
    }
}
