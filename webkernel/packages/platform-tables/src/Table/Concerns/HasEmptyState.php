<?php

namespace Webkernel\Platform\Tables\Table\Concerns;

use BackedEnum;
use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\View\TablesIconAlias;
use Webkernel\View\Htmlable;
use Webkernel\View\Contracts\View;
use Webkernel\Utilities\Arr;
use Webkernel\View\ViewResult;
use InvalidArgumentException;

trait HasEmptyState
{
    protected View | ViewResult | Htmlable | Closure | null $emptyState = null;

    protected string | Htmlable | Closure | null $emptyStateDescription = null;

    protected string | Htmlable | Closure | null $emptyStateHeading = null;

    protected string | BackedEnum | Htmlable | Closure | null $emptyStateIcon = null;

    /**
     * @var array<Action | ActionGroup>
     */
    protected array $emptyStateActions = [];

    public function emptyStateDescription(string | Htmlable | Closure | null $description): static
    {
        $this->emptyStateDescription = $description;

        return $this;
    }

    public function emptyState(View | ViewResult | Htmlable | Closure | null $emptyState): static
    {
        $this->emptyState = $emptyState;

        return $this;
    }

    /**
     * @param  array<Action | ActionGroup> | ActionGroup  $actions
     */
    public function emptyStateActions(array | ActionGroup $actions, bool $shouldOverwriteExistingActions = false): static
    {
        $this->emptyStateActions = [];
        $this->pushEmptyStateActions($actions, $shouldOverwriteExistingActions);

        return $this;
    }

    /**
     * @param  array<Action | ActionGroup> | ActionGroup  $actions
     */
    public function pushEmptyStateActions(array | ActionGroup $actions, bool $shouldOverwriteExistingActions = false): static
    {
        foreach (Arr::wrap($actions) as $action) {
            $action->table($this);

            if ($action instanceof ActionGroup) {
                /** @var array<string, Action> $flatActions */
                $flatActions = $action->getFlatActions();

                $this->mergeCachedFlatActions($flatActions, $shouldOverwriteExistingActions);
            } elseif ($action instanceof Action) {
                $this->cacheAction($action, $shouldOverwriteExistingActions);
            } else {
                throw new InvalidArgumentException('Table empty state actions must be an instance of [' . Action::class . '] or [' . ActionGroup::class . '].');
            }

            $this->emptyStateActions[] = $action;
        }

        return $this;
    }

    public function emptyStateHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->emptyStateHeading = $heading;

        return $this;
    }

    public function emptyStateIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function getEmptyState(): View | ViewResult | Htmlable | null
    {
        return $this->evaluate($this->emptyState);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getEmptyStateActions(): array
    {
        return $this->emptyStateActions;
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->emptyStateDescription);
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return $this->evaluate($this->emptyStateHeading) ?? __('webkernel-tables::table.empty.heading', [
            'model' => $this->getPluralModelLabel(),
        ]);
    }

    public function getEmptyStateIcon(): string | BackedEnum
    {
        return $this->evaluate($this->emptyStateIcon)
            ?? PlatformIcon::resolve(TablesIconAlias::EMPTY_STATE)
            ?? Heroicon::OutlinedXMark;
    }
}
