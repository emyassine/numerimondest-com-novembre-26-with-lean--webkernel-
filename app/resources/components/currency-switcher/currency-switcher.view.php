@props([
    'currency' => 'MAD',
    'currency_options' => [],
    'show_symbol' => false,
    'site_prefix' => '',
    'current_path' => '/',
])

@if (count($currency_options) > 1)
    <form class="nm-currency-switcher" action="{{ rtrim($site_prefix, '/') }}/currency" method="post">
        @csrf
        <input type="hidden" name="return_to" value="{{ $current_path }}">
        <details class="nm-currency-switcher-menu">
            <summary aria-label="Choose quote currency">
                <span style="text-transform: uppercase;">{{ $currency }}</span>
                <x-webkernel::icon icon="chevron-down" set="lucide" width="12" height="12" aria-hidden="true" />
            </summary>
            <div class="nm-currency-switcher-options" role="group" aria-label="Choose quote currency">
                @foreach ($currency_options as $currency_code => $option)
                    <button type="submit" name="currency" value="{{ $currency_code }}" @if ($currency === $currency_code) aria-current="true" @endif>
                        <span class="nm-currency-switcher-flag" aria-hidden="true">{!! flag_markup($option['flag'] ?? '') !!}</span>
                        <span class="nm-currency-switcher-label">{{ $option['label'] ?? $currency_code }}</span>
                        <span class="nm-currency-switcher-code">{{ $currency_code }}</span>
                        @if ($show_symbol && is_string($option['symbol'] ?? null))
                            <bdi aria-hidden="true">{{ $option['symbol'] }}</bdi>
                        @endif
                    </button>
                @endforeach
            </div>
        </details>
    </form>
@endif
