@props([
    'site_prefix' => '/',
    'home' => '/',
    'shortlist_count' => 0,
    'currency' => 'MAD',
    'currency_options' => [],
    'currency_config' => [],
    'request_path' => '/',
    'studio_config' => [],
])
@php
    $default_currency = is_string($currency_config['default'] ?? null) ? $currency_config['default'] : 'MAD';
    $ordered_currency_options = \App\Studio\CurrencyRates::strongestFirst($currency_options, $default_currency);
    $nav_base = rtrim($site_prefix, '/');
    $nav_items = [
        'left' => [
            ['key' => 'services', 'href' => $nav_base.'/services', 'label' => 'services', 'mobile_label' => 'Services'],
            ['key' => 'projects', 'href' => $nav_base.'/projects', 'label' => 'projects', 'mobile_label' => 'Projects'],
        ],
        'right' => [
            ['key' => 'about', 'href' => $nav_base.'/about', 'label' => 'about', 'mobile_label' => 'About'],
            ['key' => 'shortlist', 'href' => $nav_base.'/shortlist', 'label' => 'shortlist', 'mobile_label' => 'Shortlist'],
            ['key' => 'currency', 'type' => 'currency'],
            ['key' => 'contact', 'href' => $nav_base.'/contact', 'label' => 'contact', 'mobile_label' => 'Contact'],
        ],
        'footer' => [],
    ];
    foreach (($studio_config['social'] ?? []) as $network => $url) {
        $nav_items['footer'][] = ['key' => 'social', 'href' => $url, 'label' => $network, 'external' => true];
    }
    $nav_items['footer'][] = ['key' => 'client-space', 'href' => $studio_config['crm']['client_space'], 'label' => 'Client space', 'external' => true];
    $nav_items['footer'][] = ['key' => 'client-registration', 'href' => $studio_config['crm']['client_registration'], 'label' => 'Create account', 'external' => true];
    $nav_items['footer'][] = ['key' => 'staff-login', 'href' => $studio_config['crm']['staff_login'], 'label' => 'Staff login', 'external' => true];
    $nav_items['footer'][] = ['key' => 'phone', 'href' => $studio_config['contact']['phone']['href'], 'label' => $studio_config['contact']['phone']['display'], 'class' => 'nm-footer-email'];
    $nav_items['footer'][] = ['key' => 'privacy', 'href' => $nav_base.'/privacy', 'label' => 'Privacy policy', 'class' => 'nm-footer-privacy'];
    $nav_items['footer'][] = ['key' => 'pricing-policy', 'href' => $nav_base.'/pricing-policy', 'label' => 'Pricing & engagement', 'class' => 'nm-footer-privacy'];
@endphp

<style>
.nm-header {
  position: fixed;
  inset: 0 0 auto;
  z-index: 50;
  padding: 8px 50px;
  display: grid;
  grid-template-columns: 1fr 180px 1fr;
  justify-content: space-between;
  align-items: center;
  color: var(--nm-ink);
  background: transparent;
  border-bottom: 1px solid transparent;
  transition: background-color 260ms ease, border-color 260ms ease, color 180ms ease;
}

.nm-header[data-scroll-state="down"],
.nm-header[data-scroll-state="up"] {
  background: var(--nm-paper);
  border-bottom-color: var(--nm-line);
  color: var(--nm-ink);
}

.nm-header[data-logo-tone="light"] { color: var(--nm-paper); }
.nm-header[data-scroll-state="down"][data-logo-tone="light"],
.nm-header[data-scroll-state="up"][data-logo-tone="light"] { color: var(--nm-ink); }

.nm-nav {
  width: auto;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 30px;
}

.nm-nav--right { justify-content: flex-end; }
.nm-nav--compact { display: none; }
.nm-mobile-menu-currency-trigger {
  width: 100%;
  min-height: 48px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  border: 0;
  color: inherit;
  background: transparent;
  font: 500 14px/1.35 var(--nm-body);
  letter-spacing: .06em;
  text-align: left;
  cursor: pointer;
}
.nm-mobile-menu-currency-trigger strong { margin-left: auto; font-weight: 600; }
.nm-mobile-menu-currency-trigger svg { flex: 0 0 auto; }
.nm-nav-menu-trigger { box-sizing: border-box; width: 44px; height: 44px; display: grid; place-items: center; padding: 0; border: 0; background: transparent; cursor: pointer; color: inherit; }
.nm-nav-menu-trigger svg { width: 26px; height: 26px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

.nm-nav a,
.nm-nav .nm-currency-switcher-trigger,
.nm-language {
  color: inherit;
  font: 600 10px/1.2 var(--nm-body);
  letter-spacing: .12em;
  text-decoration: none;
  text-transform: uppercase;
}

.nm-nav a:hover { opacity: .56; }

.nm-wordmark {
  position: relative;
  display: grid;
  place-items: center;
  justify-self: center;
  width: 180px;
  height: 32px;
  color: inherit;
  text-decoration: none;
}

.nm-wordmark img {
  display: block;
  position: absolute;
  object-fit: contain;
  opacity: 0;
  transform: translateY(4px) scale(.97);
  transition: opacity 240ms ease, transform 300ms cubic-bezier(.2,.7,.2,1);
  pointer-events: none;
}

.nm-wordmark .logo-wordmark-dark,
.nm-wordmark .logo-wordmark-light { width: 100%; height: 24px; }
.nm-wordmark .logo-mark-dark,
.nm-wordmark .logo-mark-light { width: 24px; height: 26px; }
.nm-header[data-scroll-state="down"] .logo-wordmark-dark { opacity: 1; transform: translateY(0) scale(1); }
.nm-header[data-scroll-state="down"] .logo-wordmark-light { opacity: 0; }
.nm-header[data-scroll-state="top"] .logo-mark-dark,
.nm-header[data-scroll-state="up"] .logo-mark-dark { opacity: 1; transform: translateY(0) scale(1); }
.nm-header[data-scroll-state="top"][data-logo-tone="light"] .logo-mark-dark,
.nm-header[data-scroll-state="up"][data-logo-tone="light"] .logo-mark-dark { opacity: 0; }
.nm-header[data-scroll-state="top"][data-logo-tone="light"] .logo-mark-light,
.nm-header[data-scroll-state="up"][data-logo-tone="light"] .logo-mark-light { opacity: 1; transform: translateY(0) scale(1); }
.nm-header[data-scroll-state="down"][data-logo-tone="light"] .logo-wordmark-dark { opacity: 0; }
.nm-header[data-scroll-state="down"][data-logo-tone="light"] .logo-wordmark-light { opacity: 1; transform: translateY(0) scale(1); }

.nm-menu-currency-option { display: flex; width: 100%; align-items: center; gap: 9px; }
.nm-menu-currency-option > span:first-child { display: grid; place-items: center; }
.nm-menu-currency-option svg { width: 18px; height: 13px; }
.nm-menu-currency-option small { margin-left: auto; color: var(--text-secondary, #777); font-size: 10px; }


.nm-skip { position: absolute; left: -10000px; top: auto; }

.nm-skip:focus {
  left: 14px;
  top: 14px;
  z-index: 100;
  padding: 10px 14px;
  background: #fff;
}

/* Collapse both link groups only when they no longer fit beside the wordmark. */
@media (max-width: 900px) {
  .nm-header { grid-template-columns: 1fr 180px 1fr; }
  .nm-nav--left,
  .nm-nav--right { display: none; }
  .nm-nav--compact { grid-column: 3; justify-self: end; display: block; }
}

@media (max-width: 700px) {
  .nm-header,
  .nm-header[data-scroll-state="down"],
  .nm-header[data-scroll-state="up"],
  .nm-header[data-logo-tone="light"],
  .nm-header[data-scroll-state][data-logo-tone] {
    padding: 8px 20px;
    grid-template-columns: 1fr 200px 1fr;
    color: var(--nm-ink);
    background: var(--nm-paper);
    border-bottom-color: var(--nm-line);
    transition: none;
  }

  /* Keep the menu button in its own fixed-width slot on narrow screens. */
  .nm-header { display: flex; justify-content: space-between; }
  .nm-wordmark {
    position: absolute;
    left: 50%;
    width: min(200px, calc(100vw - 112px));
    transform: translateX(-50%);
  }
  .nm-nav--compact { display: block; margin-left: auto; flex: 0 0 auto; z-index: 2; }
  .nm-nav-menu-trigger { flex: 0 0 44px; }

  .nm-header,
  .nm-header *,
  .nm-header *::before,
  .nm-header *::after {
    transition: none;
    animation: none;
  }

  .nm-wordmark { width: 200px; height: 38px; }
  .nm-wordmark .logo-wordmark-dark,
  .nm-wordmark .logo-wordmark-light { height: 32px; }
  .nm-header[data-scroll-state][data-logo-tone] .logo-wordmark-dark {
    opacity: 1;
    transform: none;
  }
  .nm-header[data-scroll-state][data-logo-tone] .logo-wordmark-light,
  .nm-header[data-scroll-state][data-logo-tone] .logo-mark-dark,
  .nm-header[data-scroll-state][data-logo-tone] .logo-mark-light {
    opacity: 0;
    transform: none;
  }
}

@media (max-width: 360px) {
  .nm-header,
  .nm-header[data-scroll-state="down"],
  .nm-header[data-scroll-state="up"],
  .nm-header[data-logo-tone="light"],
  .nm-header[data-scroll-state][data-logo-tone] { grid-template-columns: 1fr 170px 1fr; }
  .nm-wordmark { width: 170px; height: 34px; }
  .nm-wordmark { width: min(170px, calc(100vw - 104px)); }
  .nm-wordmark .logo-wordmark-dark,
  .nm-wordmark .logo-wordmark-light { height: 28px; }
}

@media (prefers-reduced-motion: reduce) {
  .nm-header,
  .nm-wordmark img { transition: none; }
}
</style>
<style>
.nm-footer {
  padding: 25px 50px;
  display: grid;
  grid-template-columns: 1fr;
  align-items: center;
  justify-items: center;
  gap: 30px;
}

.nm-footer-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; align-items: center; }

.nm-footer-links a,
.nm-footer-email,
.nm-footer-privacy,
.nm-footer-copy {
  color: var(--nm-ink);
  font: 400 10px/1.2 var(--nm-body);
  letter-spacing: .12em;
  text-decoration: none;
  text-transform: uppercase;
}

.nm-footer-links a:hover,
.nm-footer-email:hover,
.nm-footer-privacy:hover { opacity: .56; }

.nm-footer-copy { text-align: center; }
.nm-footer-email, .nm-footer-privacy { justify-self: center; text-align: center; }

@media (max-width: 700px) {
  .nm-footer { padding: 25px 20px; gap: 20px; }
  .nm-footer-links { gap: 15px; }
}
</style>

<header class="nm-header" data-scroll-state="down" data-logo-tone="dark">
    <nav class="nm-nav nm-nav--left" aria-label="Main navigation">
        @foreach ($nav_items['left'] as $item)
            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
    <a class="nm-wordmark" href="{{ $home }}" aria-label="Numerimondes home">
        <img class="logo-wordmark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-wordmark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-darkmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-darkmode.png') }}" alt="" aria-hidden="true">
    </a>
    <nav class="nm-nav nm-nav--right" aria-label="Site pages">
        @foreach ($nav_items['right'] as $item)
            @if (($item['type'] ?? null) === 'currency')
                <x-studio::currency-switcher
                    :currency="$currency"
                    :currency-options="$ordered_currency_options"
                    :default-currency="$default_currency"
                    :show-symbol="($currency_config['show_symbol'] ?? false) === true"
                    :site-prefix="$site_prefix"
                    :current-path="$request_path"
                />
            @else
                <a href="{{ $item['href'] }}" @if ($item['key'] === 'shortlist') data-shortlist-link @endif>{{ $item['label'] }}
                    @if ($item['key'] === 'shortlist' && $shortlist_count > 0)
                        <span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>{{ $shortlist_count }}</span></x-webkernel-platform::badge></span>
                    @endif
                    @if ($item['key'] === 'shortlist')
                        <template data-shortlist-badge-template><span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>0</span></x-webkernel-platform::badge></span></template>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>
    <div class="nm-nav--compact">
        <x-webkernel-platform::dropdown
            placement="bottom-end"
            width="xs"
            offset="4"
            maxHeight="min(60dvh, 24rem)"
            shift
            teleport>
            <x-slot name="trigger">
                <button type="button" class="nm-nav-menu-trigger" aria-label="Open site menu" title="Open site menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
                </button>
            </x-slot>
            <nav aria-label="Main navigation">
                <x-webkernel-platform::dropdown.list>
                    @foreach (array_merge($nav_items['left'], $nav_items['right']) as $item)
                        @if (($item['type'] ?? null) !== 'currency')
                            <x-webkernel-platform::dropdown.list.item
                                tag="a"
                                :href="$item['href']"
                                :aria-current="$request_path === $item['href'] ? 'page' : null"
                                :data-shortlist-link="$item['key'] === 'shortlist' ? true : null"
                            >
                                <span>{{ $item['mobile_label'] }}</span>
                                @if ($item['key'] === 'shortlist' && $shortlist_count > 0)
                                    <span data-shortlist-count>{{ $shortlist_count }}</span>
                                @endif
                            </x-webkernel-platform::dropdown.list.item>
                        @endif
                    @endforeach
                </x-webkernel-platform::dropdown.list>
            </nav>

            @if (count($currency_options) > 1)
                <div class="nm-mobile-currency-switcher">
                    <x-webkernel-platform::dropdown
                        placement="bottom-start"
                        width="xs"
                        offset="4"
                        maxHeight="min(60dvh, 24rem)"
                        shift
                        teleport>
                        <x-slot name="trigger">
                            <button type="button" class="nm-mobile-menu-currency-trigger" role="menuitem" tabindex="-1" aria-label="Choose currency">
                                <span>Currency</span>
                                <strong>{{ $currency }}</strong>
                                <x-webkernel::icon icon="chevron-down" set="lucide" width="14" height="14" aria-hidden="true" />
                            </button>
                        </x-slot>
                        <form action="{{ rtrim($site_prefix, '/') }}/currency" method="post">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $request_path }}">
                            <x-webkernel-platform::dropdown.list>
                                @foreach ($ordered_currency_options as $currencyCode => $option)
                                    <x-webkernel-platform::dropdown.list.item
                                        tag="button"
                                        type="submit"
                                        name="currency"
                                        :value="$currencyCode"
                                        :aria-current="$currency === $currencyCode ? 'true' : null"
                                    >
                                        <span aria-hidden="true">{!! flag_markup($option['flag'] ?? '') !!}</span>
                                        <span>{{ $option['label'] ?? $currencyCode }}</span>
                                        <small>{{ $currencyCode }}</small>
                                    </x-webkernel-platform::dropdown.list.item>
                                @endforeach
                            </x-webkernel-platform::dropdown.list>
                        </form>
                    </x-webkernel-platform::dropdown>
                </div>
            @endif
        </x-webkernel-platform::dropdown>
    </div>
</header>

{{ $subheader ?? '' }}

<div id="main">{{ $slot }}</div>

<footer class="nm-footer" id="contact" style="margin-top:5rem;">
    <span class="nm-footer-copy" style="margin-bottom:0rem;">
        © {{ $studio_config['company']['name'] }} · {{ $studio_config['company']['location'] }} — Page Load took {{ number_format(request_time(), 2, ',', ' ') . ' ms' }}
    </span>
    <div class="nm-footer-links">
        @foreach ($nav_items['footer'] as $item)
            <a href="{{ $item['href'] }}" class="{{ $item['class'] ?? '' }}" @if ($item['external'] ?? false) target="_blank" rel="noreferrer" @endif>{{ $item['label'] }}</a>
        @endforeach
    </div>
</footer>
