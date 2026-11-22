@php
    use Webkernel\Platform\Support\Enums\IconSize;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Utilities\Number;

    $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();

    $databaseNotificationsLabel = $unreadNotificationsCount
        ? trans_choice('webkernel-platform::layout.actions.open_database_notifications.label_with_unread_count', $unreadNotificationsCount, ['count' => Number::format($unreadNotificationsCount, locale: \Webkernel\I18n\I18nContext::get_locale())])
        : __('webkernel-platform::layout.actions.open_database_notifications.label');
@endphp

<button
    @if ($isSidebarCollapsibleOnDesktop)
        x-bind:aria-label="$store.sidebar.isOpen ? null : @js($databaseNotificationsLabel)"
    @endif
    class="fi-sidebar-database-notifications-btn"
>
    {{ \Webkernel\Platform\Support\generate_icon_html(Heroicon::OutlinedBell, alias: PanelsIconAlias::SIDEBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON, size: IconSize::Large) }}

    <span
        @if ($isSidebarCollapsibleOnDesktop)
            x-show="$store.sidebar.isOpen"
            x-transition:enter="fi-transition-enter"
            x-transition:enter-start="fi-transition-enter-start"
            x-transition:enter-end="fi-transition-enter-end"
        @endif
        class="fi-sidebar-database-notifications-btn-label"
    >
        {{ __('webkernel-platform::layout.actions.open_database_notifications.label') }}
    </span>

    @if ($unreadNotificationsCount)
        <span
            @if ($isSidebarCollapsibleOnDesktop)
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            @endif
            class="fi-sidebar-database-notifications-btn-badge-ctn"
        >
            <x-webkernel-platform::badge>
                {{ $unreadNotificationsCount }}
            </x-webkernel-platform::badge>
        </span>
    @endif
</button>
