<?php

namespace Webkernel\Platform\Models\Contracts;

interface HasAvatar
{
    public function getPlatformAvatarUrl(): ?string;
}
