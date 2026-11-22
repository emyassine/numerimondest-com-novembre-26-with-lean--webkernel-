<?php

namespace Webkernel\Platform\Auth\Http\Controllers;

use Webkernel\Platform\Auth\Http\Requests\BlockEmailChangeVerificationRequest;
use Webkernel\Platform\Auth\Http\Responses\Contracts\BlockEmailChangeVerificationResponse;
use Webkernel\Platform\Notifications\Notification;

class BlockEmailChangeVerificationController
{
    public function __invoke(BlockEmailChangeVerificationRequest $request): BlockEmailChangeVerificationResponse
    {
        $isSuccessful = $request->fulfill();

        if ($isSuccessful) {
            Notification::make()
                ->title(__('webkernel-platform::auth/http/controllers/block-email-change-verification-controller.notifications.blocked.title'))
                ->body(__('webkernel-platform::auth/http/controllers/block-email-change-verification-controller.notifications.blocked.body', [
                    'email' => decrypt($request->route('email')),
                ]))
                ->success()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title(__('webkernel-platform::auth/http/controllers/block-email-change-verification-controller.notifications.failed.title'))
                ->body(__('webkernel-platform::auth/http/controllers/block-email-change-verification-controller.notifications.failed.body', [
                    'email' => decrypt($request->route('email')),
                ]))
                ->danger()
                ->persistent()
                ->send();
        }

        return app(BlockEmailChangeVerificationResponse::class);
    }
}
