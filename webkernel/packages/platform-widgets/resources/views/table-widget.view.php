@php
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Widgets\View\WidgetsRenderHook;
@endphp

<x-webkernel-widgets::widget class="fi-wi-table">
    {{ PlatformView::renderHook(WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

    {{ $this->table ?? null }}

    {{ PlatformView::renderHook(WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}
</x-webkernel-widgets::widget>
