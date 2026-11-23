@props([
    'currency' => 'MAD',
    'currency_options' => [],
    'show_symbol' => false,
    'site_prefix' => '',
    'current_path' => '/',
])

<style>
    .nm-currency-switcher-trigger {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0;
        border: 0;
        color: inherit;
        background: transparent;
        font: 400 10px/1.2 var(--nm-body);
        letter-spacing: .12em;
        text-transform: uppercase;
        cursor: pointer;
    }
    .nm-currency-switcher-trigger svg { width: 11px; height: 11px; }
</style>

@if (count($currency_options) > 1)
    <x-webkernel-platform::dropdown placement="bottom-end" width="xs" offset="4" maxHeight="min(60dvh, 24rem)" shift teleport>
        <x-slot name="trigger">
            <button type="button" class="nm-currency-switcher-trigger" aria-label="Choose quote currency">
                <span>{{ $currency }}</span>
                <x-webkernel::icon icon="chevron-down" set="lucide" width="12" height="12" aria-hidden="true" />
            </button>
        </x-slot>
        <form action="{{ rtrim($site_prefix, '/') }}/currency" method="post">
            @csrf
            <input type="hidden" name="return_to" value="{{ $current_path }}">
            <x-webkernel-platform::dropdown.list>
                @foreach ($currency_options as $currency_code => $option)
                    <x-webkernel-platform::dropdown.list.item
                        tag="button"
                        type="submit"
                        name="currency"
                        :value="$currency_code"
                        :aria-current="$currency === $currency_code ? 'true' : null"
                    >
                        {!! flag_markup($option['flag'] ?? '') !!}
                        <span>{{ $option['label'] ?? $currency_code }}</span>
                        <small>{{ $currency_code }}</small>
                        @if ($show_symbol && is_string($option['symbol'] ?? null))
                            <bdi aria-hidden="true">{{ $option['symbol'] }}</bdi>
                        @endif
                    </x-webkernel-platform::dropdown.list.item>
                @endforeach
            </x-webkernel-platform::dropdown.list>
        </form>
    </x-webkernel-platform::dropdown>
@endif
