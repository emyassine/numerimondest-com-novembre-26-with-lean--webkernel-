<?php

namespace Webkernel\Platform\Widgets\ChartWidget\Concerns;

use BackedEnum;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Widgets\View\WidgetsIconAlias;
use Webkernel\View\Htmlable;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

trait HasEmptyState
{
    protected ?string $emptyStateDescription = null;

    protected ?string $emptyStateHeading = null;

    protected string | BackedEnum | null $emptyStateIcon = null;

    public function getEmptyState(): View | ViewResult | Htmlable | null
    {
        return null;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getEmptyStateActions(): array
    {
        return [];
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return $this->emptyStateDescription;
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return $this->emptyStateHeading ?? __('webkernel-widgets::chart.empty.heading');
    }

    public function getEmptyStateIcon(): string | BackedEnum | Htmlable
    {
        return $this->emptyStateIcon
            ?? PlatformIcon::resolve(WidgetsIconAlias::CHART_WIDGET_EMPTY_STATE)
            ?? Heroicon::OutlinedXMark;
    }
}
