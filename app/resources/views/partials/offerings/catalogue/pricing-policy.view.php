<section class="nm-services-notes" aria-label="Service notes">
    <p class="nm-marketplace-pricing-note">
        @if ($currency === 'MAD')
            MAD is the catalogue reference currency. Starting prices reflect senior-led architecture and execution; final scope, timelines, and multi-mandate terms are confirmed in a written proposal prior to commencement.
        @else
            {{ $currency }} prices are indicative conversions, buffered and rounded for readability. Reference rate date: {{ $rateMetadata['rate_date'] !== '' ? $rateMetadata['rate_date'] : 'not available' }}@if ($rateMetadata['checked_at'] !== '') (checked {{ $rateMetadata['checked_at'] }} UTC)@endif. Final currency and fee are confirmed in a written proposal.
        @endif
        <a href="{{ simple_site('studio', 'pricing-policy') }}">Pricing &amp; engagement policy</a>.
    </p>
</section>
