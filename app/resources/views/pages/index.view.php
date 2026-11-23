<x-studio::page title="Numerimondes — Ideas, Strategy, Execution" description="Independent consulting and solutions based in Casablanca, Morocco. Business insight, technology and specialist expertise for lasting value." robots="index,follow">
    <style>
        .nm-home-explore { padding: 70px 0 0; }
        .nm-home-explore h2 {
            margin: 0 0 28px;
            font: 400 12px/1.5 var(--nm-title);
            letter-spacing: .16em;
            text-align: center;
            text-transform: uppercase;
        }
        .nm-home-explore-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .nm-home-explore-card {
            min-height:210px; padding:26px 28px; display:flex; flex-direction:column; justify-content:space-between;
            color:var(--nm-ink); text-decoration:none; border:1px solid var(--border-default, var(--nm-line)); border-radius:var(--radius-md, .375rem); background:var(--surface-panel, #fff); box-shadow:0 1px 2px rgba(15,23,42,.06), 0 8px 24px rgba(15,23,42,.04);
        }
        .nm-home-explore-card h3 { margin:24px 0 10px; font:400 13px/1.45 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-home-explore-card p { max-width:340px; margin:0; font:400 16px/1.4 var(--nm-body); }
        .nm-home-opportunity { padding-inline:0; }
        @media (max-width:800px) { .nm-home-explore-grid { grid-template-columns:1fr; } .nm-home-explore-card { min-height:160px; } }
        @media (max-width:700px) {
            .nm-home-explore { padding:45px 0 0; }
            .nm-home-explore h2 { margin-bottom:20px; font-size:10px; }
            .nm-home-explore-card { padding:22px 20px; }
            .nm-home-explore-card h3 { font-size:11px; }
            .nm-home-explore-card p { font-size:14px; }
        }
    </style>
    <main class="nm-main" style="min-height:100vh">
        @php
            $serviceCount = count(\App\Studio\ServiceCatalog::all());
            $heroPositioningText = 'Explore '.$serviceCount.' services to strengthen your brand, grow your sales pipeline and improve how work gets done — with a dedicated team shaped around your goals.';
        @endphp
        <x-studio::hero-effect
            class="bleed-page"
            :effect="['silk', ['inject' => ['lightColor' => '#0043e0', 'darkColor' => '#141D2A', 'lightNoise' => 1.5, 'noiseIntensity' => 0.3, 'speed' => 12]]]"
            :hero-style="['minHeight' => '90vh']"
            :title="['text' => 'Operations that move<br><span class=\'silky-ri\'>businesses forward</span>', 'color' => ['light' => '#e2e8f0', 'dark' => '#e2e8f0']]"
            :description="['text' => $heroPositioningText, 'color' => ['light' => '#e2e8f0', 'dark' => '#e2e8f0']]"
            :buttons="[
                ['label' => 'Explore services', 'href' => simple_site('studio', 'services').'#marketplace', 'color' => 'primary'],
            ]"
        >
        </x-studio::hero-effect>

        <section class="nm-home-explore" aria-labelledby="nm-home-explore-title">
            <h2 id="nm-home-explore-title">Explore Numerimondes</h2>
            <div class="nm-home-explore-grid">
                <a class="nm-home-explore-card" href="{{ simple_site('studio', 'services') }}">
                    <div><h3>Services</h3><p>Explore defined offers, starting prices and what each includes.</p></div>
                    <x-webkernel::icon icon="arrow-up-right" set="lucide" width="18" height="18" class="nm-icon-arrow nm-icon-arrow--large" aria-hidden="true" />
                </a>
                <a class="nm-home-explore-card" href="{{ simple_site('studio', 'projects') }}">
                    <div><h3>Projects</h3><p>Discover the ventures and services we are building and delivering.</p></div>
                    <x-webkernel::icon icon="arrow-up-right" set="lucide" width="18" height="18" class="nm-icon-arrow nm-icon-arrow--large" aria-hidden="true" />
                </a>
            </div>
        </section>

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
