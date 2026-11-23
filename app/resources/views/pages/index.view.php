<x-studio::page title="Numerimondes — Ideas, Strategy, Execution" description="Independent consulting and solutions based in Casablanca, Morocco. Business insight, technology and specialist expertise for lasting value." robots="index,follow" :page-width="['desktop' => '1200px']">
    <style>
        .nm-home-opportunity { padding-inline:0; }
    </style>
    <main class="nm-main" style="min-height:100vh">

        @php
            $hero = 2;
        @endphp

        @if ($hero === 2)
            <style>
                .nm-home-hero-2 { width:100%; max-width:none; margin:0; padding:0 0 4.5rem; }
                .nm-home-hero-2__top,
                .nm-home-hero-2__bottom { display:grid; grid-template-columns:1.35fr .85fr; gap:4rem; }
                .nm-home-hero-2__top { min-height:80vh; align-items:center; margin:0;  }
                .nm-home-hero-2__right { display:flex; flex-direction:column; align-items:flex-start; gap:1.25rem; }
                .nm-home-hero-2__bottom { padding-top:0rem; }
                .nm-home-hero-2__capabilities { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.25rem 2rem; }
                .nm-home-hero-2__capability { padding-top:.75rem; border-top:1px solid var(--border-default, #e2e8f0); }
                .nm-home-hero-2__tags { display:flex; flex-wrap:wrap; gap:.5rem; margin:0 0 1rem; }
                @media(max-width:960px) {
                    .nm-home-hero-2__top,
                    .nm-home-hero-2__bottom { grid-template-columns:1fr; gap:2.5rem; }
                    .nm-home-hero-2__capabilities { grid-template-columns:repeat(2,minmax(0,1fr)); }
                }
                @media(max-width:700px) { .nm-home-hero-2 { padding-bottom:3rem; } .nm-home-hero-2__top { min-height:80vh; padding-block:3rem; } }
                @media(max-width:540px) { .nm-home-hero-2__capabilities { grid-template-columns:1fr; } }
            </style>
            <section class="nm-home-hero-2">
                <div class="nm-home-hero-2__top">
                    <div>
                        <p class="label-text eyebrow-label">Advisory · Systems · Global Execution</p>
                        <h1 class="h1-silky">Move with purpose.<br><em>Complex execution,</em><br>delivered cleanly.</h1>
                    </div>
                    <div class="nm-home-hero-2__right">
                        <p class="lead-text secondary-text">The world moves fast. We help your business move with precision—strengthening operations, grounding decisions in frontline research, and building the systems and teams required to turn ambitious mandates into reality.</p>
                        <x-webkernel-platform::button tag="a" :href="simple_site('studio', 'contact')" color="primary">Discuss a Mandate <span aria-hidden="true">→</span></x-webkernel-platform::button>
                        <p class="small-text secondary-text">We accept a very limited number of client engagements at any time to ensure direct principal involvement.</p>
                    </div>
                </div>

                <div class="nm-home-hero-2__bottom">
                    <section aria-labelledby="nm-home-practice-title">
                        <h2 class="label-text section-label" id="nm-home-practice-title">Practice Areas</h2>
                        <div class="nm-home-hero-2__capabilities">
                            @php
                                $capabilities = [
                                    ['title' => 'Operations Advisory', 'description' => 'Research, market intelligence, and structural execution.'],
                                    ['title' => 'Business Systems', 'description' => 'Bespoke software architecture and internal operational tooling.'],
                                    ['title' => 'Remote Recruitment', 'description' => 'Hand-vetted specialists aligned to exact operational briefs.'],
                                    ['title' => 'Brand Identity', 'description' => 'Institutional positioning, narrative, and visual systems.'],
                                    ['title' => 'Digital Presence', 'description' => 'High-craft web platforms built for clarity and permanence.'],
                                    ['title' => 'Sales Development', 'description' => 'Structured market entry and qualified commercial pipelines.'],
                                ];
                            @endphp
                            @foreach ($capabilities as $capability)
                                <div class="nm-home-hero-2__capability">
                                    <h3 class="card-heading">{{ $capability['title'] }}</h3>
                                    <p class="small-text secondary-text">{{ $capability['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <div>
                        <section aria-labelledby="nm-home-ventures-title">
                            <h2 class="label-text section-label" id="nm-home-ventures-title">Internal Projects // Ventures</h2>
                            <x-webkernel-platform::section :compact="true" class="nm-home-hero-2__ventures">
                                <p class="supporting-text secondary-text">Beyond client mandates, we research, architect, and incubate proprietary concepts—from luxury maisons to Rust-based application frameworks.</p>
                                <div class="nm-home-hero-2__tags" aria-label="Studio ventures">
                                    @php
                                        $ventures = [
                                            ['name' => 'Caldezza', 'color' => 'success', 'icon' => 'gem'],
                                            ['name' => 'Feluqa', 'color' => 'danger', 'icon' => 'code-2'],
                                            ['name' => 'The Best Recruit', 'color' => 'info', 'icon' => 'user-round-search'],
                                            ['name' => 'Virtual Assistant Asia', 'color' => 'gray', 'icon' => 'globe-2'],
                                        ];
                                    @endphp
                                    @foreach ($ventures as $venture)
                                        <x-webkernel-platform::badge :color="$venture['color']" size="sm">
                                            <x-webkernel::icon :icon="$venture['icon']" set="lucide" width="14" height="14" aria-hidden="true" />
                                            {{ $venture['name'] }}
                                        </x-webkernel-platform::badge>
                                    @endforeach
                                </div>
                                <x-webkernel-platform::button tag="a" :href="simple_site('studio', 'projects')" color="gray" size="xs"><x-webkernel::icon icon="arrow-up-right" set="lucide" width="12" height="12" aria-hidden="true" /> Explore our projects</x-webkernel-platform::button>
                            </x-webkernel-platform::section>
                        </section>
                    </div>
                </div>
            </section>
        @elseif ($hero === 3)
            {{-- Reserved for a future hero variation. --}}
        @endif
        <section class="nm-about-section nm-home-opportunity">
            <div>
                <h2>What could your next opportunity become?</h2>
            </div>
            <div>
                <p>
                    Share a challenge, an idea or a business objective. Yassine, our founder, and the account manager personally review each request and follow up to understand what would make a meaningful difference.
                    <a href="{{ simple_site('studio', 'contact') }}">Request a private discussion</a>.
                </p>
            </div>
        </section>
    </main>
</x-studio::page>
