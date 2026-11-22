@props([
    'navigation',
])

@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
@endphp

<x-webkernel-platform::dropdown
    placement="bottom-start"
    width="xs"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-page-sub-navigation-dropdown'])
    "
>
    <x-slot name="trigger">
        @php
            $activeItem = null;

            foreach ($navigation as $navigationGroup) {
                foreach ($navigationGroup->getItems() as $navigationItem) {
                    foreach ([$navigationItem, ...$navigationItem->getChildItems()] as $navigationItemChild) {
                        if ($navigationItemChild->isActive()) {
                            $activeItem = $navigationItemChild;

                            break 3;
                        }
                    }
                }
            }
        @endphp

        <x-webkernel-platform::button
            color="gray"
            :icon="Heroicon::ChevronDown"
            :icon-alias="PanelsIconAlias::SUB_NAVIGATION_MOBILE_MENU_BUTTON"
            icon-position="after"
        >
            {{ $activeItem?->getLabel() }}
        </x-webkernel-platform::button>
    </x-slot>

    @foreach ($navigation as $navigationGroup)
        @if (filled($navigationGroupLabel = $navigationGroup->getLabel()))
            <x-webkernel-platform::dropdown.header>
                {{ $navigationGroupLabel }}
            </x-webkernel-platform::dropdown.header>
        @endif

        <x-webkernel-platform::dropdown.list>
            @foreach ($navigationGroup->getItems() as $navigationItem)
                @foreach ([$navigationItem, ...$navigationItem->getChildItems()] as $navigationItemChild)
                    @php
                        $navigationItemBadge = $navigationItem->getBadge();
                        $navigationItemBadgeColor = $navigationItem->getBadgeColor($navigationItemBadge);
                        $navigationItemIcon = $navigationItem->isActive() ? ($navigationItem->getActiveIcon() ?? $navigationItem->getIcon()) : $navigationItem->getIcon();
                        $navigationItemUrl = $navigationItem->getUrl();
                        $shouldNavigationItemOpenUrlInNewTab = $navigationItem->shouldOpenUrlInNewTab();
                        $navigationItemExtraAttributes = $navigationItemChild->getExtraAttributeBag();
                    @endphp

                    <x-webkernel-platform::dropdown.list.item
                        :badge="$navigationItemBadge"
                        :badge-color="$navigationItemBadgeColor"
                        :href="$navigationItemUrl"
                        :icon="$navigationItemIcon"
                        tag="a"
                        :target="$shouldNavigationItemOpenUrlInNewTab ? '_blank' : null"
                        :aria-current="$navigationItemChild->isActive() ? 'page' : null"
                        :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($navigationItemExtraAttributes)"
                    >
                        {{ $navigationItemChild->getLabel() }}
                    </x-webkernel-platform::dropdown.list.item>
                @endforeach
            @endforeach
        </x-webkernel-platform::dropdown.list>
    @endforeach
</x-webkernel-platform::dropdown>
