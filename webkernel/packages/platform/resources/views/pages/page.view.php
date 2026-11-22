@props(['livewire'])
@php($livewire = $livewire ?? $__webkernel_component)
<x-webkernel-platform::page :livewire="$livewire">
    {{ $livewire->content }}
</x-webkernel-platform::page>
