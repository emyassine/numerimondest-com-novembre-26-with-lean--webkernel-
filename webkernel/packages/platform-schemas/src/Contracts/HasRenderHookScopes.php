<?php

namespace Webkernel\Platform\Schemas\Contracts;

interface HasRenderHookScopes
{
    /**
     * @return array<string>
     */
    public function getRenderHookScopes(): array;
}
