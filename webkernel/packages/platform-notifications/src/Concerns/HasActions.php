<?php

namespace Webkernel\Platform\Notifications\Concerns;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Support\Enums\Size;
use Webkernel\Utilities\Arr;

trait HasActions
{
    /**
     * @var array<Action | ActionGroup> | ActionGroup | Closure
     */
    protected array | ActionGroup | Closure $actions = [];

    /**
     * @param  array<Action | ActionGroup> | ActionGroup | Closure  $actions
     */
    public function actions(array | ActionGroup | Closure $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getActions(): array
    {
        return array_map(
            fn (Action | ActionGroup $action) => match (true) {
                $action instanceof Action => $action->defaultView(Action::LINK_VIEW)->defaultSize(Size::Small),
                $action instanceof ActionGroup => $action->defaultTriggerView(ActionGroup::LINK_VIEW)->defaultSize(Size::Small)
            },
            Arr::wrap($this->evaluate($this->actions)),
        );
    }
}
