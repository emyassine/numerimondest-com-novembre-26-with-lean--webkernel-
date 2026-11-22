<?php

namespace Webkernel\Platform\Tables\Columns\Concerns;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\ORM\Model;

trait CanCallAction
{
    protected Closure | Action | string | null $action = null;

    public function action(Closure | Action | string | null $action): static
    {
        if (is_string($action)) {
            $action = function (HasTable $livewire, ?Model $record) use ($action) {
                if ($record) {
                    return $livewire->{$action}($record);
                }

                return $livewire->{$action}();
            };
        }

        $this->action = $action;

        return $this;
    }

    public function getAction(): Closure | Action | null
    {
        return $this->action;
    }
}
