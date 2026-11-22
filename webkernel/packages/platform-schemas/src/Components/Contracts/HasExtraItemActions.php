<?php

namespace Webkernel\Platform\Schemas\Components\Contracts;

use Webkernel\Platform\Actions\Action;

interface HasExtraItemActions
{
    /**
     * @return array<Action>
     */
    public function getExtraItemActions(): array;

    /**
     * @return array<string, mixed>
     */
    public function getItemState(string $key): array;
}
