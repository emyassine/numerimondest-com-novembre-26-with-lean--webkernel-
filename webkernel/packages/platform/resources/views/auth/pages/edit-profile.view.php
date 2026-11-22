@php
    $pageComponent = static::isSimple() ? 'webkernel-platform::page.simple' : 'webkernel-platform::page';
@endphp

<x-dynamic-component :component="$pageComponent">
    {{ $this->content }}
</x-dynamic-component>
