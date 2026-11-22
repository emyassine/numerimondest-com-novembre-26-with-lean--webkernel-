@props([
    'navigation',
])

@php
    use Webkernel\View\Htmlable;
@endphp

<x-webkernel-platform::tabs
    :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($attributes)->class(['fi-page-sub-navigation-tabs'])"
>
    @foreach ($navigation as $navigationGroup)
        @php
            $navigationGroupLabel = $navigationGroup->getLabel();
            $isNavigationGroupActive = $navigationGroup->isActive();
            $navigationGroupIcon = $navigationGroup->getIcon();
        @endphp

        @if ($navigationGroupLabel)
            <x-webkernel-platform::dropdown placement="bottom-start">
                <x-slot name="trigger">
                    <x-webkernel-platform::tabs.item
                        :active="$isNavigationGroupActive"
                        :icon="$navigationGroupIcon"
                    >
                        {{ $navigationGroupLabel }}
                    </x-webkernel-platform::tabs.item>
                </x-slot>

                <x-webkernel-platform::dropdown.list>
                    @foreach ($navigationGroup->getItems() as $navigationItem)
                        @php
                            $navigationItemBadge = $navigationItem->getBadge();
                            $navigationItemBadgeColor = $navigationItem->getBadgeColor($navigationItemBadge);
                            $navigationItemBadgeTooltip = $navigationItem->getBadgeTooltip($navigationItemBadge);
                            $navigationItemIcon = $navigationItem->isActive() ? ($navigationItem->getActiveIcon() ?? $navigationItem->getIcon()) : $navigationItem->getIcon();
                            $navigationItemUrl = $navigationItem->getUrl();
                            $shouldNavigationItemOpenUrlInNewTab = $navigationItem->shouldOpenUrlInNewTab();
                            $navigationItemExtraAttributes = $navigationItem->getExtraAttributeBag();
                        @endphp

                        <x-webkernel-platform::dropdown.list.item
                            :badge="$navigationItemBadge"
                            :badge-color="$navigationItemBadgeColor"
                            :badge-tooltip="$navigationItemBadgeTooltip"
                            :href="$navigationItemUrl"
                            :icon="$navigationItemIcon"
                            tag="a"
                            :target="$shouldNavigationItemOpenUrlInNewTab ? '_blank' : null"
                            :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($navigationItemExtraAttributes)"
                        >
                            {{ $navigationItem->getLabel() }}

                            @if ($navigationItemIcon instanceof Htmlable)
                                <x-slot name="icon">
                                    {{ $navigationItemIcon }}
                                </x-slot>
                            @endif
                        </x-webkernel-platform::dropdown.list.item>
                    @endforeach
                </x-webkernel-platform::dropdown.list>
            </x-webkernel-platform::dropdown>
        @else
            @foreach ($navigationGroup->getItems() as $navigationItem)
                @php
                    $isNavigationItemActive = $navigationItem->isActive();
                    $navigationItemBadge = $navigationItem->getBadge();
                    $navigationItemBadgeColor = $navigationItem->getBadgeColor($navigationItemBadge);
                    $navigationItemBadgeTooltip = $navigationItem->getBadgeTooltip($navigationItemBadge);
                    $navigationItemIcon = $navigationItem->isActive() ? ($navigationItem->getActiveIcon() ?? $navigationItem->getIcon()) : $navigationItem->getIcon();
                    $navigationItemUrl = $navigationItem->getUrl();
                    $shouldNavigationItemOpenUrlInNewTab = $navigationItem->shouldOpenUrlInNewTab();
                    $navigationItemExtraAttributes = $navigationItem->getExtraAttributeBag();
                @endphp

                <x-webkernel-platform::tabs.item
                    :active="$isNavigationItemActive"
                    :badge="$navigationItemBadge"
                    :badge-color="$navigationItemBadgeColor"
                    :badge-tooltip="$navigationItemBadgeTooltip"
                    :href="$navigationItemUrl"
                    :icon="$navigationItemIcon"
                    tag="a"
                    :target="$shouldNavigationItemOpenUrlInNewTab ? '_blank' : null"
                    :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($navigationItemExtraAttributes)"
                >
                    {{ $navigationItem->getLabel() }}

                    @if ($navigationItemIcon instanceof Htmlable)
                        <x-slot name="icon">
                            {{ $navigationItemIcon }}
                        </x-slot>
                    @endif
                </x-webkernel-platform::tabs.item>
            @endforeach
        @endif
    @endforeach
</x-webkernel-platform::tabs>
