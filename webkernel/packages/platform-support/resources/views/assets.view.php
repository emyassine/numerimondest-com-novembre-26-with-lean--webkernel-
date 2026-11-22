@php
    $nonce = $csp_nonce ?? null;
@endphp

@if (isset($data))

    <script @if (is_string($nonce) && $nonce !== '') nonce="{{ $nonce }}" @endif>
        window.platformData = @js($data)
    </script>
@endif

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

<style @if (is_string($nonce ?? null) && $nonce !== '') nonce="{{ $nonce }}" @endif>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }

    @foreach ($customColors ?? [] as $customColorName => $customColorShades) .fi-color-{{ $customColorName }} { @foreach ($customColorShades as $customColorShade) --color-{{ $customColorShade }}:var(--{{ $customColorName }}-{{ $customColorShade }}); @endforeach } @endforeach
</style>
