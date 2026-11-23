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
            $hero = random_int(1, 2);
        @endphp

        @if ($hero === 1)
          <x-studio::hero-effect
              class="bleed-page"
              :effect="['silk', ['inject' => ['lightColor' => '#0043e0', 'darkColor' => '#141D2A', 'lightNoise' => 1.5, 'noiseIntensity' => 0.3, 'speed' => 12]]]"
              :hero-style="['minHeight' => '90vh']"
              :eyebrow="['text' => 'Achieve the extraordinary', 'color' => ['light' => '#e2e8f0', 'dark' => '#e2e8f0']]"
              :title="['text' => 'Solve what’s slowing your business down.', 'color' => ['light' => '#e2e8f0', 'dark' => '#e2e8f0']]"
              :description="['text' => 'The world moves fast. We help your business move with purpose—strengthening operations, turning ambitious ideas into action, and opening new opportunities.', 'color' => ['light' => '#e2e8f0', 'dark' => '#e2e8f0']]"
              :buttons="[
                  ['label' => 'Plan your next move', 'href' => simple_site('studio', 'contact'), 'color' => 'primary'],
                  ['label' => 'Explore services', 'href' => simple_site('studio', 'services').'#marketplace', 'color' => 'gray'],
              ]"
          >
          </x-studio::hero-effect>
        @elseif ($hero === 2)
            <style>
                .nm-home-hero-2 { width:100%; max-width:none; margin:0; padding:5.5rem 0 4.5rem; }
                .nm-home-hero-2__top,
                .nm-home-hero-2__bottom { display:grid; grid-template-columns:1.35fr .85fr; gap:4rem; }
                .nm-home-hero-2__top { align-items:end; padding-bottom:4.5rem; border-bottom:1px solid var(--border-hairline); }
                .nm-home-hero-2__eyebrow,
                .nm-home-hero-2__label { display:block; color:var(--ink-muted); font-size:.75rem; font-weight:600; letter-spacing:.14em; text-transform:uppercase; }
                .nm-home-hero-2__eyebrow { margin:0 0 1.5rem; }
                .nm-home-hero-2 h1 { margin:0; color:var(--ink-primary); font-size:clamp(2.5rem,5vw,4rem); font-weight:400; line-height:1.08; }
                .nm-home-hero-2 h1 em { color:var(--ink-secondary); font-style:italic; }
                .nm-home-hero-2__right { display:flex; flex-direction:column; align-items:flex-start; gap:1.25rem; }
                .nm-home-hero-2__lead { margin:0; color:var(--ink-secondary); font-size:1.075rem; line-height:1.7; }
                .nm-home-hero-2__note { margin:0; color:var(--ink-muted); font-size:.8rem; line-height:1.45; }
                .nm-home-hero-2__bottom { padding-top:3rem; }
                .nm-home-hero-2__label { margin:0 0 1.25rem; }
                .nm-home-hero-2__capabilities { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.25rem 2rem; }
                .nm-home-hero-2__capability { padding-top:.75rem; border-top:1px solid var(--border-subtle); }
                .nm-home-hero-2__capability h3 { margin:0 0 .25rem; color:var(--ink-primary); font-size:.9rem; font-weight:600; }
                .nm-home-hero-2__capability p,
                .nm-home-hero-2__ventures p { margin:0; color:var(--ink-muted); font-size:.8rem; line-height:1.5; }
                .nm-home-hero-2__ventures p { margin-bottom:1rem; color:var(--ink-secondary); font-size:.85rem; line-height:1.6; }
                .nm-home-hero-2__tags { display:flex; flex-wrap:wrap; gap:.5rem; margin:0 0 1rem; padding:0; list-style:none; }
                .nm-home-hero-2__tags li { padding:.25rem .65rem; border:1px solid var(--border-subtle); background:var(--bg-slate); color:var(--ink-primary); font-size:.75rem; }
                .nm-home-hero-2__link { color:var(--ink-primary); font-size:.82rem; font-weight:600; text-decoration:none; }
                .nm-home-hero-2__link:hover { text-decoration:underline; }
                @media(max-width:960px) {
                    .nm-home-hero-2__top,
                    .nm-home-hero-2__bottom { grid-template-columns:1fr; gap:2.5rem; }
                    .nm-home-hero-2__capabilities { grid-template-columns:repeat(2,minmax(0,1fr)); }
                }
                @media(max-width:700px) { .nm-home-hero-2 { padding-block:3rem; } }
                @media(max-width:540px) { .nm-home-hero-2__capabilities { grid-template-columns:1fr; } }
            </style>
            <section class="nm-home-hero-2">
                <div class="nm-home-hero-2__top">
                    <div>
                        <p class="nm-home-hero-2__eyebrow">Advisory · Systems · Global Execution</p>
                        <h1>Move with purpose.<br><em>Complex execution,</em><br>delivered cleanly.</h1>
                    </div>
                    <div class="nm-home-hero-2__right">
                        <p class="nm-home-hero-2__lead">The world moves fast. We help your business move with precision—strengthening operations, grounding decisions in frontline research, and building the systems and teams required to turn ambitious mandates into reality.</p>
                        <x-webkernel-platform::button tag="a" :href="simple_site('studio', 'contact')" color="primary">Discuss a Mandate <span aria-hidden="true">→</span></x-webkernel-platform::button>
                        <p class="nm-home-hero-2__note">We accept a very limited number of client engagements at any time to ensure direct principal involvement.</p>
                    </div>
                </div>

                <div class="nm-home-hero-2__bottom">
                    <section aria-labelledby="nm-home-practice-title">
                        <h2 class="nm-home-hero-2__label" id="nm-home-practice-title">Practice Areas</h2>
                        <div class="nm-home-hero-2__capabilities">
                            <div class="nm-home-hero-2__capability"><h3>Operations Advisory</h3><p>Primary research, market intelligence, and structural execution.</p></div>
                            <div class="nm-home-hero-2__capability"><h3>Business Systems</h3><p>Bespoke software architecture and internal operational tooling.</p></div>
                            <div class="nm-home-hero-2__capability"><h3>Remote Recruitment</h3><p>Individually vetted specialists matched to exact operational briefs.</p></div>
                            <div class="nm-home-hero-2__capability"><h3>Brand Identity</h3><p>Institutional positioning, narrative, and visual systems.</p></div>
                            <div class="nm-home-hero-2__capability"><h3>Digital Presence</h3><p>High-craft web platforms built for clarity and permanence.</p></div>
                            <div class="nm-home-hero-2__capability"><h3>Sales Development</h3><p>Structured market entry and qualified commercial pipelines.</p></div>
                        </div>
                    </section>
                    <section aria-labelledby="nm-home-ventures-title">
                        <h2 class="nm-home-hero-2__label" id="nm-home-ventures-title">Internal Studio &amp; Ventures</h2>
                        <x-webkernel-platform::section :compact="true" class="nm-home-hero-2__ventures">
                            <p>Beyond client mandates, we research, architect, and incubate proprietary concepts—from luxury maisons to Rust-based application frameworks.</p>
                            <ul class="nm-home-hero-2__tags">
                                <li>Caldezza</li><li>Feluqa</li><li>The Best Recruit</li><li>Virtual Assistant Asia</li>
                            </ul>
                            <a class="nm-home-hero-2__link" href="{{ simple_site('studio', 'projects') }}">Explore ideas in development →</a>
                        </x-webkernel-platform::section>
                    </section>
                </div>
            </section>
        @elseif ($hero === 3)
            {{-- Reserved for a future hero variation. --}}
        @endif
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
