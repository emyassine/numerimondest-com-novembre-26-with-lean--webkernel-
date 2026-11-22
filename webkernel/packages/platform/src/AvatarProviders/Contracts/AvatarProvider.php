<?php

namespace Webkernel\Platform\AvatarProviders\Contracts;

use Webkernel\ORM\Model;

interface AvatarProvider
{
    public function get(Model $record): string;
}
