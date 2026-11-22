<?php

namespace Webkernel\Liveview\Features\SupportTesting {

    use Webkernel\Platform\Notifications\Notification;

    class Testable {
        public function assertNotified(Notification | string $notification = null): static {}
        
        public function assertNotNotified(Notification | string $notification = null): static {}
    }

}
