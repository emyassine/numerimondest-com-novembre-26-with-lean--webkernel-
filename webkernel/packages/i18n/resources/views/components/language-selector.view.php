@props([
    'context' => 'topbar',
    'languages' => null,
    'show_flags' => true,
    'urls' => [],
])
@php
$current = \function_exists('i18n_current_lang') ? i18n_current_lang() : 'en';
$codes = \is_array($languages) && $languages !== [] ? $languages : ['en', 'fr', 'ar', 'es', 'de', 'pt'];
$back = \parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';
$context = \in_array($context, ['topbar', 'user-menu', 'sidebar'], true) ? $context : 'topbar';
$is_topbar = $context === 'topbar';
$rtl = \function_exists('i18n_is_rtl') && i18n_is_rtl();
$placement = $is_topbar ? ($rtl ? 'bottom-end' : 'bottom-start') : 'top-end';
$available = \function_exists('fast_i18n')
    ? fast_i18n(['en' => 'Available Languages', 'fr' => 'Langues disponibles', 'es' => 'Idiomas disponibles', 'ar' => 'اللغات المتاحة'], null, 'i18n_language_selector_available_languages')
    : 'Available Languages';
$empty = \function_exists('fast_i18n')
    ? fast_i18n(['en' => 'No Available Languages', 'fr' => 'Aucune langue disponible', 'es' => 'No hay idiomas disponibles', 'ar' => 'لا توجد لغات متاحة'], null, 'i18n_language_selector_no_available_languages')
    : 'No Available Languages';
$tooltip = \function_exists('fast_i18n')
    ? fast_i18n(['en' => 'Switch Language', 'fr' => 'Changer de langue', 'es' => 'Cambiar idioma', 'ar' => 'تغيير اللغة'], null, 'i18n_language_selector_switch_language')
    : 'Switch Language';
$current_label = \function_exists('i18n_catalog_language_label') ? i18n_catalog_language_label($current) : $current;
$show_flags = \is_bool($show_flags) ? $show_flags : true;
$urls = \is_array($urls) ? $urls : [];
@endphp
<div class="w-lang w-lang-{{ $context }}">
    <x-webkernel::dropdown :placement="$placement" width="sm">
        <x-slot name="trigger">
            <button type="button" class="w-lang-trigger" title="{{ $tooltip }}" aria-label="{{ $tooltip }}">
                @if ($show_flags)<span class="w-lang-flag" aria-hidden="true">{!! flag_markup($current) !!}</span>@endif
                <span class="w-lang-code">{{ strtoupper($current) }}</span>
                <x-webkernel::icon icon="chevron-down" aria-hidden="true" />
                @if (! $is_topbar)<span class="w-lang-trigger-label">{{ $current_label }}</span>@endif
            </button>
        </x-slot>
        <x-webkernel::dropdown.header icon="languages">{{ $available }}</x-webkernel::dropdown.header>
        @if ($urls !== [])
            <x-webkernel::dropdown.list class="w-lang-list">
                @forelse ($codes as $code)
                    @php
                        $code = (string) $code;
                        $label = \function_exists('i18n_catalog_language_label') ? i18n_catalog_language_label($code) : $code;
                        $selected = $code === $current;
                    @endphp
                    <x-webkernel::dropdown.list.item tag="a" :href="$urls[$code] ?? null" :disabled="! isset($urls[$code])" :selected="$selected" :lang="$code" :hreflang="$code">
                        @if ($show_flags)<span class="w-lang-flag" aria-hidden="true">{!! flag_markup($code) !!}</span>@endif
                        <span>{{ $label }}</span>
                        @if ($selected)<x-webkernel::icon icon="check" aria-hidden="true" />@endif
                    </x-webkernel::dropdown.list.item>
                @empty
                    <p class="w-lang-empty">{{ $empty }}</p>
                @endforelse
            </x-webkernel::dropdown.list>
        @else
            <form method="post" action="/locale" class="w-lang-form">
                {!! \Webkernel\Csrf::field() !!}
                <input type="hidden" name="_back" value="{{ $back }}" />
                <x-webkernel::dropdown.list class="w-lang-list">
                    @forelse ($codes as $code)
                        @php
                            $code = (string) $code;
                            $label = \function_exists('i18n_catalog_language_label') ? i18n_catalog_language_label($code) : $code;
                            $selected = $code === $current;
                        @endphp
                        <x-webkernel::dropdown.list.item type="submit" name="locale" :value="$code" :selected="$selected">
                            @if ($show_flags)<span class="w-lang-flag" aria-hidden="true">{!! flag_markup($code) !!}</span>@endif
                            <span>{{ $label }}</span>
                            @if ($selected)<x-webkernel::icon icon="check" aria-hidden="true" />@endif
                        </x-webkernel::dropdown.list.item>
                    @empty
                        <p class="w-lang-empty">{{ $empty }}</p>
                    @endforelse
                </x-webkernel::dropdown.list>
            </form>
        @endif
    </x-webkernel::dropdown>
</div>
<style nonce="{{ $csp_nonce }}">
.w-lang { position:relative; flex-shrink:0; }
.w-lang-user-menu,.w-lang-sidebar,.w-lang-user-menu .fi-dropdown,.w-lang-sidebar .fi-dropdown { width:100%; }
.w-lang-trigger { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-width:42px; min-height:32px; padding:4px 6px; border:0; border-radius:4px; background:transparent; color:var(--webkernel-muted-foreground,var(--zinc-500)); font-family:inherit; font-size:11px; font-weight:500; line-height:1.4; cursor:pointer; }
.w-lang-trigger:hover { background:var(--zinc-100); color:var(--zinc-950); }
.dark .w-lang-trigger:hover { background:var(--zinc-900); color:var(--zinc-50); }
.w-lang-trigger:focus-visible { outline:2px solid var(--zinc-500); outline-offset:2px; }
.w-lang-trigger svg { width:13px; height:13px; }
.w-lang-user-menu .w-lang-trigger,.w-lang-sidebar .w-lang-trigger { width:100%; justify-content:flex-start; padding:8px 12px; min-height:40px; font-size:14px; color:var(--webkernel-foreground,inherit); }
.w-lang-trigger-label { flex:1 1 0%; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:start; }
.w-lang-code { letter-spacing:.035em; }
.w-lang-flag { width:16px; height:16px; min-width:16px; min-height:16px; display:block; overflow:hidden; border-radius:2px; line-height:0; flex:0 0 16px; }
.w-lang-trigger .w-lang-flag { width:18px; height:18px; min-width:18px; min-height:18px; flex-basis:18px; }
.w-lang-flag svg,.w-lang-flag-svg { width:100%; height:100%; display:block; }
.w-lang .fi-dropdown-panel { z-index:80; border-radius:4px; }
.w-lang-list { max-height:min(15rem,50vh); overflow-x:hidden; overflow-y:auto; scrollbar-width:thin; }
.w-lang .fi-dropdown-list-item-label { display:flex; align-items:center; gap:8px; min-width:0; }
.w-lang .fi-dropdown-list-item.fi-selected { color:var(--zinc-900); background:var(--zinc-50); }
.dark .w-lang .fi-dropdown-list-item.fi-selected { color:var(--zinc-50); background:var(--zinc-900); }
.w-lang-empty { padding:12px 16px; font-size:13px; color:var(--webkernel-muted-foreground,var(--zinc-500)); text-align:center; font-style:italic; }
</style>
