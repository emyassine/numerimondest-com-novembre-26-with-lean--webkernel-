<?php

namespace Webkernel\Platform\Pages;

use BackedEnum;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Panel;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\EmbeddedSchema;
use Webkernel\Platform\Schemas\Components\Grid;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use Webkernel\Platform\Widgets\Widget;
use Webkernel\Platform\Widgets\WidgetConfiguration;
use Webkernel\View\Htmlable;

class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string | Htmlable
    {
        return static::$navigationLabel ??
            static::$title ??
            __('webkernel-platform::pages/dashboard.title');
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? PlatformIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? (Platform::hasTopNavigation() ? Heroicon::Home : Heroicon::OutlinedHome);
    }

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return Platform::getWidgets();
    }

    /**
     * @deprecated Use `getWidgetsSchemaComponents($this->getWidgets())` to transform widgets into schema components instead, which also filters their visibility.
     *
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return 2;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('webkernel-platform::pages/dashboard.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getFiltersFormContentComponent(): Component
    {
        return EmbeddedSchema::make('filtersForm');
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets()));
    }
}
