@props([
    'site_prefix' => '/',
    'home' => '/',
    'shortlist_count' => 0,
    'currency' => 'MAD',
    'currency_options' => [],
    'currency_config' => [],
    'request_path' => '/',
])

<header class="nm-header" data-scroll-state="down" data-logo-tone="dark">
    <nav class="nm-nav nm-nav--left" aria-label="Main navigation">
        <a href="{{ $site_prefix }}/services">services</a>
        <a href="{{ $site_prefix }}/projects">projects</a>
    </nav>
    <a class="nm-wordmark" href="{{ $home }}" aria-label="Numerimondes home">
        <img class="logo-wordmark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-wordmark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-darkmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-darkmode.png') }}" alt="" aria-hidden="true">
    </a>
    <nav class="nm-nav nm-nav--right" aria-label="Site pages">
        <a href="{{ $site_prefix }}/about">about</a>
        <a href="{{ $site_prefix }}/shortlist" data-shortlist-link>shortlist
            @if ($shortlist_count > 0)
                <span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>{{ $shortlist_count }}</span></x-webkernel-platform::badge></span>
            @endif
            <template data-shortlist-badge-template><span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>0</span></x-webkernel-platform::badge></span></template>
        </a>
        <x-studio::currency-switcher
            :currency="$currency"
            :currency-options="$currency_options"
            :show-symbol="($currency_config['show_symbol'] ?? false) === true"
            :site-prefix="$site_prefix"
            :current-path="$request_path"
        />
        <a href="{{ $site_prefix }}/contact">contact</a>
    </nav>
    <div class="nm-nav--compact">
        <details class="nm-mobile-menu">
            <summary class="nm-nav-menu-trigger" aria-label="Open site menu" title="Open site menu">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
            </summary>
            <div class="nm-mobile-menu-panel">
                <nav aria-label="Main navigation">
                    <a href="{{ $site_prefix }}/services">Services</a>
                    <a href="{{ $site_prefix }}/projects">Projects</a>
                    <a href="{{ $site_prefix }}/about">About</a>
                    <a href="{{ $site_prefix }}/shortlist" data-shortlist-link>Shortlist @if ($shortlist_count > 0)<span data-shortlist-count>{{ $shortlist_count }}</span>@endif</a>
                    <a href="{{ $site_prefix }}/contact">Contact</a>
                </nav>

                @if (count($currency_options) > 1)
                    <div class="nm-mobile-menu-heading">Currency · {{ $currency }}</div>
                    <form action="{{ rtrim($site_prefix, '/') }}/currency" method="post">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $request_path }}">
                        @foreach ($currency_options as $currencyCode => $option)
                            <button class="nm-mobile-menu-currency" type="submit" name="currency" value="{{ $currencyCode }}" @if ($currency === $currencyCode) aria-current="true" @endif>
                                <span aria-hidden="true">{!! flag_markup($option['flag'] ?? '') !!}</span>
                                <span>{{ $option['label'] ?? $currencyCode }}</span>
                                <small>{{ $currencyCode }}</small>
                            </button>
                        @endforeach
                    </form>
                @endif
            </div>
        </details>
    </div>
</header>

<style nonce="{{ $csp_nonce ?? '' }}">
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
.nm-mobile-menu { position: relative; }
.nm-mobile-menu summary { list-style: none; }
.nm-mobile-menu summary::-webkit-details-marker { display: none; }
.nm-mobile-menu-panel { display: none; }
.nm-mobile-menu[open] .nm-mobile-menu-panel {
  position: fixed;
  inset: calc(56px + env(safe-area-inset-top)) 12px auto auto;
  z-index: 100;
  display: block;
  width: min(320px, calc(100vw - 24px));
  max-height: calc(100dvh - 76px - env(safe-area-inset-top));
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 8px 0;
  border: 1px solid var(--nm-line);
  border-radius: 12px;
  background: #fff;
  color: var(--nm-ink);
  box-shadow: 0 12px 36px rgb(0 0 0 / 16%);
}
.nm-mobile-menu-panel nav { display: grid; }
.nm-mobile-menu-panel nav a,
.nm-mobile-menu-currency {
  min-height: 48px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  color: inherit;
  font: 500 14px/1.35 var(--nm-title);
  letter-spacing: .06em;
  text-decoration: none;
}
.nm-mobile-menu-panel nav a + a,
.nm-mobile-menu-currency { border-top: 1px solid var(--nm-line); }
.nm-mobile-menu-heading { padding: 14px 18px 8px; border-top: 1px solid var(--nm-line); font: 600 11px/1.3 var(--nm-title); letter-spacing: .1em; text-transform: uppercase; }
.nm-mobile-menu-currency { width: 100%; background: transparent; text-align: left; }
.nm-mobile-menu-currency small { margin-left: auto; color: #666; }
.nm-mobile-menu-currency svg { width: 18px; height: 13px; }
.nm-nav-menu-trigger { width: 44px; height: 44px; display: grid; place-items: center; cursor: pointer; color: inherit; }
.nm-nav-menu-trigger svg { width: 26px; height: 26px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

.nm-nav a,
.nm-language {
  color: inherit;
  font: 400 10px/1.2 var(--nm-title);
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

.nm-nav-menu-trigger.fi-icon-btn { color: inherit; }
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
