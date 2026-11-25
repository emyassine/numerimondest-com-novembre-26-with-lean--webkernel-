<style>
    .nm-services-filter-section { margin-bottom:2rem; }
    .nm-services-filter-grid { gap:1rem; }
</style>
<x-webkernel-platform::section class="nm-services-filter-section">
    <form id="services-filters" aria-label="Filter services">
        @php
            $filterGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
                'default' => 1, 'md' => 2, 'lg' => 5,
            ])->class('items-end nm-services-filter-grid');
            $searchInputColumn = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->gridColumn([
                'md' => 2, 'lg' => 2,
            ])->class('min-w-0');
        @endphp
        <div {{ $filterGrid }}>
            <div {{ $searchInputColumn }}>
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input id="services-query" name="q" type="search" placeholder="Search offers" aria-label="Search offers" :value="$searchQuery"></x-webkernel-platform::input>
                </x-webkernel-platform::input.wrapper>
            </div>
            <div>
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input.select id="services-practice" name="practice" aria-label="Filter by practice">
                        <option value="">All practices</option>
                        @foreach ($practiceMap as $practiceSlug => $practiceName)
                            <option value="{{ $practiceSlug }}" @selected($selectedPractice === $practiceSlug)>{{ $practiceName }}</option>
                        @endforeach
                    </x-webkernel-platform::input.select>
                </x-webkernel-platform::input.wrapper>
            </div>
            <div>
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input.select id="services-engagement" name="engagement" aria-label="Filter by engagement type">
                        <option value="">All formats</option>
                        @foreach ($engagementMap as $engagementSlug => $engagementName)
                            <option value="{{ $engagementSlug }}" @selected($selectedEngagement === $engagementSlug)>{{ $engagementName }}</option>
                        @endforeach
                    </x-webkernel-platform::input.select>
                </x-webkernel-platform::input.wrapper>
            </div>
            <div>
                <label class="nm-marketplace-priced" for="services-owner">
                    <input id="services-owner" name="owner" type="checkbox" value="1" {{ $ownerOnly ? 'checked' : '' }}>
                    <span>Owner-managed budgets (under 50,000 MAD)</span>
                </label>
            </div>
        </div>
        <div class="mt-4">
            <x-webkernel-platform::button.group>
                <x-webkernel-platform::button type="reset" color="gray" outlined size="sm" icon="heroicon-o-arrow-path" aria-label="Clear all filters" tooltip="Clear all filters"></x-webkernel-platform::button>
            </x-webkernel-platform::button.group>
        </div>
    </form>
</x-webkernel-platform::section>
