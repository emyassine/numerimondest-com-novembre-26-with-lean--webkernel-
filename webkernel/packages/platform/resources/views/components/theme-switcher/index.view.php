@php
    use Webkernel\Platform\Support\Icons\Heroicon;
@endphp

<div
    x-data="platformThemeSwitcher"
    x-init="init"
    data-default-theme="{{ platform()->getDefaultThemeMode()->value }}"
    role="group"
    aria-label="{{ __('webkernel-platform::layout.actions.theme_switcher.label') }}"
    class="fi-theme-switcher"
>
    <x-webkernel-platform::theme-switcher.button
        :icon="Heroicon::Sun"
        theme="light"
    />

    <x-webkernel-platform::theme-switcher.button
        :icon="Heroicon::Moon"
        theme="dark"
    />

    <x-webkernel-platform::theme-switcher.button
        :icon="Heroicon::ComputerDesktop"
        theme="system"
    />
</div>
