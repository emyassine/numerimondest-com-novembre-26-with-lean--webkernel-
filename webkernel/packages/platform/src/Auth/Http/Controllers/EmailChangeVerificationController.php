<?php

namespace Webkernel\Platform\Auth\Http\Controllers;

use Webkernel\Platform\Auth\Http\Requests\EmailChangeVerificationRequest;
use Webkernel\Platform\Auth\Http\Responses\Contracts\EmailChangeVerificationResponse;
use Webkernel\Platform\Notifications\Notification;

class EmailChangeVerificationController
{
    public function __invoke(EmailChangeVerificationRequest $request): EmailChangeVerificationResponse
    {
        if (! $request->fulfill()) {
            Notification::make()
                ->title(__('webkernel-platform::auth/http/controllers/email-change-verification-controller.notifications.unavailable.title'))
                ->body(__('webkernel-platform::auth/http/controllers/email-change-verification-controller.notifications.unavailable.body'))
                ->danger()
                ->send();

            return app(EmailChangeVerificationResponse::class);
        }

        Notification::make()
            ->title(__('webkernel-platform::auth/http/controllers/email-change-verification-controller.notifications.verified.title'))
            ->body(__('webkernel-platform::auth/http/controllers/email-change-verification-controller.notifications.verified.body', [
                'email' => decrypt($request->route('email')),
            ]))
            ->success()
            ->send();

        return app(EmailChangeVerificationResponse::class);
    }
}
