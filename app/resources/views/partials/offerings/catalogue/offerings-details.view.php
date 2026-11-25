<x-webkernel-platform::section id="revenue-force" heading="Revenue Force · Owner-led businesses">
    <h2>Hire the salesperson. Install the system. Manage the rhythm.</h2>
    <p>You carry the salary. We recruit, equip and supervise, so the sales function runs without becoming a second job for the owner.</p>
    <div {{ $grid }}>
        <x-webkernel-platform::card compact>
            <x-webkernel-platform::badge color="gray" size="sm">Step 1 · Recruit</x-webkernel-platform::badge>
            <h3>Salesperson selection</h3>
            <p>Sourcing, live sales simulations and selection of a field or inside salesperson, through The Best Recruit.</p>
            <p>{{ $revenueForce['recruitment_price']['display'] ?? '46% of monthly salary' }} · due on signature of the hire</p>
        </x-webkernel-platform::card>
        <x-webkernel-platform::card compact>
            <x-webkernel-platform::badge color="gray" size="sm">Step 2 · Equip</x-webkernel-platform::badge>
            <h3>CRM, scripts and qualified files</h3>
            <p>A configured CRM, qualified prospect files, the sales narrative and a tracking grid.</p>
            <p>{{ $revenueForce['setup_price']['display'] ?? '' }}</p>
        </x-webkernel-platform::card>
        <x-webkernel-platform::card compact>
            <x-webkernel-platform::badge color="gray" size="sm">Step 3 · Supervise</x-webkernel-platform::badge>
            <h3>Weekly commercial direction</h3>
            <p>A partner reviews calls and KPIs each week, coaches the salesperson and reports to the owner.</p>
            <p>{{ $revenueForce['monthly_price']['display'] ?? '' }}</p>
        </x-webkernel-platform::card>
    </div>
    @if ($revenueForce !== [])
        <x-studio::shortlist-button :service="$revenueForce" :return_to="'/services'" />
    @endif
</x-webkernel-platform::section>

<x-webkernel-platform::section id="modules" heading="Vertical Software · Early Access" style="margin-top:2rem">
    <h2>One software engine, deployed sector by sector.</h2>
    <p>Per-establishment monthly subscription, plus onboarding and data import. Join the waitlist: we prioritise the sectors that respond first.</p>
    <div {{ $grid }}>
        @foreach ($modules as $module)
            <x-webkernel-platform::card compact>
                <x-webkernel-platform::badge color="success" size="sm">Waitlist open · Early access</x-webkernel-platform::badge>
                <h3>{{ $module['name'] }}</h3>
                <p>{{ $module['scope'] }}</p>
                <p>Onboarding and data import: {{ $module['onboarding_price_display'] }}</p>
                <p><strong>From {{ $module['price_display'] }}</strong></p>
                <x-studio::shortlist-button :service="$module" :compact="true" />
            </x-webkernel-platform::card>
        @endforeach
    </div>
</x-webkernel-platform::section>

<x-webkernel-platform::section id="playbooks" heading="Capability Transfer · Executive Playbooks" style="margin-top:2rem">
    <h2>Keep control of the method. Delegate execution when you choose.</h2>
    <p>Each Playbook transfers a complete operating architecture (processes, decision grids, selection criteria, scripts and technical structure) with a working session with a partner. If you commission full execution within 30 days, the Playbook fee is credited in full.</p>
    <div {{ $grid }}>
        @foreach ($playbooks as $playbook)
            <x-webkernel-platform::card compact>
                <x-webkernel-platform::badge color="warning" size="sm">Credited in full within 30 days</x-webkernel-platform::badge>
                <h3>{{ $playbook['name'] }}</h3>
                <p>{{ $playbook['target'] }}</p>
                <p>{{ $playbook['deliverables'] }}</p>
                <p>Leads to: {{ $playbook['gateway'] }}</p>
                <p><strong>{{ $playbook['price_display'] }}</strong></p>
                <x-studio::shortlist-button :service="$playbook" :compact="true" />
            </x-webkernel-platform::card>
        @endforeach
    </div>
</x-webkernel-platform::section>

