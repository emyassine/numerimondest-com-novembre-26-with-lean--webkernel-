@props([
    'icon',
    'theme',
])

@php
    use Webkernel\Platform\View\PanelsIconAlias;

    $label = __("webkernel-platform::layout.actions.theme_switcher.{$theme}.label");
@endphp

<button
    aria-label="{{ $label }}"
    role="menuitemradio"
    tabindex="-1"
    type="button"
    data-theme="{{ $theme }}"
    data-tooltip="{{ $label }}"
    x-on:click="setTheme($el.dataset.theme)"
    x-tooltip="{
        content: $el.dataset.tooltip,
        theme: $store.theme,
    }"
    x-bind:aria-checked="ariaChecked($el.dataset.theme)"
    x-bind:class="{ 'fi-active': isActive($el.dataset.theme) }"
    class="fi-theme-switcher-btn"
>
    {{
        \Webkernel\Platform\Support\generate_icon_html($icon, alias: match ($theme) {
            'light' => PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON,
            'dark' => PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON,
            'system' => PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON,
        })
    }}
</button>
