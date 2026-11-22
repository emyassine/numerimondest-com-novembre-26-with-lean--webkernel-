<?php

namespace Webkernel\Platform\Notifications\Testing;

use Webkernel\Platform\Notifications\Notification;

/**
 * @return TestCall | TestCase | mixed
 */
function assertNotified(Notification | string | null $notification = null)
{
    Notification::assertNotified($notification);

    return test();
}
