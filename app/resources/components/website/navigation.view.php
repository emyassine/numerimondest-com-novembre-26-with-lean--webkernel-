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
                    :currency-options="$currency_options"
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
        <details class="nm-mobile-menu">
            <summary class="nm-nav-menu-trigger" aria-label="Open site menu" title="Open site menu">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
            </summary>
            <div class="nm-mobile-menu-panel">
                <nav aria-label="Main navigation">
                    @foreach (array_merge($nav_items['left'], $nav_items['right']) as $item)
                        @if (($item['type'] ?? null) !== 'currency')
                            <a href="{{ $item['href'] }}" @if ($item['key'] === 'shortlist') data-shortlist-link @endif>{{ $item['mobile_label'] }} @if ($item['key'] === 'shortlist' && $shortlist_count > 0)<span data-shortlist-count>{{ $shortlist_count }}</span>@endif</a>
                        @endif
                    @endforeach
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

<div id="main">{{ $slot }}</div>

<footer class="nm-footer" id="contact" style="margin-top:5rem;">
    <span class="nm-footer-copy" style="margin-top:3rem;margin-bottom:2rem;">
        © {{ $studio_config['company']['name'] }} · {{ $studio_config['company']['location'] }} — Page Load took {{ number_format(request_time(), 2, ',', ' ') . ' ms' }}
    </span>
    <div class="nm-footer-links">
        @foreach ($nav_items['footer'] as $item)
            <a href="{{ $item['href'] }}" class="{{ $item['class'] ?? '' }}" @if ($item['external'] ?? false) target="_blank" rel="noreferrer" @endif>{{ $item['label'] }}</a>
        @endforeach
    </div>
</footer>
