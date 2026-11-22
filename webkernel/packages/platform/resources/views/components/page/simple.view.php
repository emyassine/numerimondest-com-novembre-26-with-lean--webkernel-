@props([
    'heading' => null,
    'subheading' => null,
])

@php
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Tables\Contracts\HasTable;
    use Webkernel\Platform\View\PanelsRenderHook;

    $heading ??= $this->getHeading();
    $subheading ??= $this->getSubHeading();
    $hasLogo = $this->hasLogo();
@endphp

<div {{ $attributes->class(['fi-simple-page']) }}>
    {{ PlatformView::renderHook(PanelsRenderHook::SIMPLE_PAGE_START, scopes: $this->getRenderHookScopes()) }}

    <div class="fi-simple-page-content">
        @if (filled($heading) || $hasLogo || filled($subheading))
            <x-webkernel-platform::header.simple
                :heading="$heading"
                :logo="$hasLogo"
                :subheading="$subheading"
            />
        @endif

        {{ $slot }}
    </div>

    @if (! $this instanceof HasTable)
        <x-webkernel-actions::modals />
    @endif

    {{ PlatformView::renderHook(PanelsRenderHook::SIMPLE_PAGE_END, scopes: $this->getRenderHookScopes()) }}
</div>
