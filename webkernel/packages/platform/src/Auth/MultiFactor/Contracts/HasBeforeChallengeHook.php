<?php

namespace Webkernel\Platform\Auth\MultiFactor\Contracts;

use \Webkernel\Accounts\Contracts\Authenticatable;

interface HasBeforeChallengeHook
{
    public function beforeChallenge(Authenticatable $user): void;
}
