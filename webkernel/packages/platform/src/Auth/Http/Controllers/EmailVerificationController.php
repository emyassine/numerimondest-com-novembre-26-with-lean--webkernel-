<?php

namespace Webkernel\Platform\Auth\Http\Controllers;

use Webkernel\Platform\Auth\Http\Responses\Contracts\EmailVerificationResponse;
use \Webkernel\Accounts\Http\EmailVerificationRequest;

class EmailVerificationController
{
    public function __invoke(EmailVerificationRequest $request): EmailVerificationResponse
    {
        $request->fulfill();

        return app(EmailVerificationResponse::class);
    }
}
