@props([
    'position' => null,
])

@php
    use Webkernel\Platform\Actions\Action;
    use Webkernel\Platform\Enums\UserMenuPosition;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Platform\View\PanelsRenderHook;
    use Webkernel\Utilities\Arr;

    $user = platform()->auth()->user();

    $userName = platform()->getUserName($user);

    $items = $this->getUserMenuItems();

    $itemsBeforeAndAfterThemeSwitcher = collect($items)
        ->groupBy(fn (Action $item): bool => $item->getSort() < 0, preserveKeys: true)
        ->all();
    $itemsBeforeThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[true] ?? collect();
    $itemsAfterThemeSwitcher = $itemsBeforeAndAfterThemeSwitcher[false] ?? collect();

    $hasProfileHeader = $itemsBeforeThemeSwitcher->has('profile') &&
        blank(($item = Arr::first($itemsBeforeThemeSwitcher))->getUrl()) &&
        (! $item->hasAction());

    if ($itemsBeforeThemeSwitcher->has('profile')) {
        $itemsBeforeThemeSwitcher = $itemsBeforeThemeSwitcher->prepend($itemsBeforeThemeSwitcher->pull('profile'), 'profile');
    }

    $multiGroupAfterTheme = $this->hasMultipleUserMenuItemGroups();
    $afterThemeItemGroups = $multiGroupAfterTheme ? $this->getUserMenuItemGroupsAfterTheme() : [];

    $position ??= platform()->getUserMenuPosition();

    $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();
@endphp

{{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_BEFORE) }}

<x-webkernel-platform::dropdown
    :placement="($position === UserMenuPosition::Topbar) ? 'bottom-end' : 'top-end'"
    :teleport="$position === UserMenuPosition::Topbar"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-menu'])
    "
>
    <x-slot name="trigger">
        @if ($position === UserMenuPosition::Topbar)
            <button
                aria-label="{{ __('webkernel-platform::layout.actions.open_user_menu.label') }}"
                type="button"
                class="fi-user-menu-trigger"
            >
                <x-webkernel-platform::avatar.user :user="$user" loading="lazy" />
            </button>
        @else
            <button
                aria-label="{{ filled($userName) ? $userName : __('webkernel-platform::layout.actions.open_user_menu.label') }}"
                type="button"
                class="fi-user-menu-trigger"
            >
                <x-webkernel-platform::avatar.user :user="$user" loading="lazy" />

                <span
                    @if ($isSidebarCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-user-menu-trigger-text"
                >
                    {{ $userName }}
                </span>

                {{
                    \Webkernel\Platform\Support\generate_icon_html(Heroicon::ChevronUp, alias: PanelsIconAlias::USER_MENU_TOGGLE_BUTTON, attributes: new ComponentAttributeBag([
                        'x-show' => $isSidebarCollapsibleOnDesktop ? '$store.sidebar.isOpen' : null,
                    ]))
                }}
            </button>
        @endif
    </x-slot>

    @if ($hasProfileHeader)
        @php
            $item = $itemsBeforeThemeSwitcher['profile'];
            $itemColor = $item->getColor();
            $itemIcon = $item->getIcon();

            unset($itemsBeforeThemeSwitcher['profile']);
        @endphp

        {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

        <x-webkernel-platform::dropdown.header :color="$itemColor" :icon="$itemIcon">
            {{ $item->getLabel() }}
        </x-webkernel-platform::dropdown.header>

        {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
    @endif

    @if ($itemsBeforeThemeSwitcher->isNotEmpty())
        <x-webkernel-platform::dropdown.list>
            @foreach ($itemsBeforeThemeSwitcher as $key => $item)
                @if ($key === 'profile')
                    {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                    {{ $item }}

                    {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                @else
                    {{ $item }}
                @endif
            @endforeach
        </x-webkernel-platform::dropdown.list>
    @endif

    @if (platform()->hasDarkMode() && (! platform()->hasDarkModeForced()) && platform()->hasThemeSwitcher())
        <x-webkernel-platform::dropdown.list>
            <x-webkernel-platform::theme-switcher />
        </x-webkernel-platform::dropdown.list>
    @endif

    @if ($multiGroupAfterTheme && $afterThemeItemGroups !== [])
        @foreach ($afterThemeItemGroups as $afterThemeGroup)
            <x-webkernel-platform::dropdown.list>
                @foreach ($afterThemeGroup as $key => $item)
                    @if ($key === 'profile')
                        {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                        {{ $item }}

                        {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                    @else
                        {{ $item }}
                    @endif
                @endforeach
            </x-webkernel-platform::dropdown.list>
        @endforeach
    @elseif ($itemsAfterThemeSwitcher->isNotEmpty())
        <x-webkernel-platform::dropdown.list>
            @foreach ($itemsAfterThemeSwitcher as $key => $item)
                @if ($key === 'profile')
                    {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_BEFORE) }}

                    {{ $item }}

                    {{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER) }}
                @else
                    {{ $item }}
                @endif
            @endforeach
        </x-webkernel-platform::dropdown.list>
    @endif
</x-webkernel-platform::dropdown>

{{ PlatformView::renderHook(PanelsRenderHook::USER_MENU_AFTER) }}
