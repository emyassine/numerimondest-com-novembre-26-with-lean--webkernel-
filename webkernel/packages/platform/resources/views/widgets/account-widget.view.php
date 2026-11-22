@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;

    $user = platform()->auth()->user();
@endphp

<x-webkernel-widgets::widget class="fi-account-widget">
    <x-webkernel-platform::section>
        <x-webkernel-platform::avatar.user
            size="lg"
            :user="$user"
            loading="lazy"
        />

        <div class="fi-account-widget-main">
            <h2 class="fi-account-widget-heading">
                {{ __('webkernel-platform::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
            </h2>

            <p class="fi-account-widget-user-name">
                {{ platform()->getUserName($user) }}
            </p>
        </div>

        <form
            action="{{ platform()->getLogoutUrl() }}"
            method="post"
            class="fi-account-widget-logout-form"
        >
            @csrf

            <x-webkernel-platform::button
                color="gray"
                :icon="Heroicon::ArrowLeftEndOnRectangle"
                :icon-alias="PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON"
                labeled-from="sm"
                tag="button"
                type="submit"
            >
                {{ __('webkernel-platform::widgets/account-widget.actions.logout.label') }}
            </x-webkernel-platform::button>
        </form>
    </x-webkernel-platform::section>
</x-webkernel-widgets::widget>
