@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Utilities\Number;
@endphp

<x-webkernel-platform::icon-button
    :badge="$unreadNotificationsCount ?: null"
    color="gray"
    :icon="Heroicon::OutlinedBell"
    :icon-alias="PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON"
    icon-size="lg"
    :label="
        $unreadNotificationsCount
        ? trans_choice('webkernel-platform::layout.actions.open_database_notifications.label_with_unread_count', $unreadNotificationsCount, ['count' => Number::format($unreadNotificationsCount, locale: \Webkernel\I18n\I18nContext::get_locale())])
        : __('webkernel-platform::layout.actions.open_database_notifications.label')
    "
    class="fi-topbar-database-notifications-btn"
/>
