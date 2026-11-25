@if ($services !== [])
    @foreach ($practiceMap as $practiceSlug => $practiceName)
        @if (! empty($servicesByPractice[$practiceSlug]))
            @php
                $practiceServices = $servicesByPractice[$practiceSlug];
                $marketplaceId = 'marketplace-'.$practiceSlug;
                $marketplaceLabel = 'Services in '.$practiceName;
            @endphp
            <section class="nm-marketplace-practice-group" data-practice="{{ $practiceSlug }}" aria-labelledby="nm-marketplace-practice-{{ $practiceSlug }}">
                <header class="nm-marketplace-practice-heading">
                    <h2 id="nm-marketplace-practice-{{ $practiceSlug }}">{{ $practiceName }}</h2>
                    <span data-practice-count>{{ count($servicesByPractice[$practiceSlug]) }} {{ count($servicesByPractice[$practiceSlug]) === 1 ? 'offer' : 'offers' }}</span>
                </header>
                @include('studio::marketplace-list', ['services' => $practiceServices, 'id' => $marketplaceId, 'label' => $marketplaceLabel])
            </section>
        @endif
    @endforeach
@endif
