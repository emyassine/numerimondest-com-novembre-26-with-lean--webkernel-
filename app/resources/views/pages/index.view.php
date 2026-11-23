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

        @if(false)
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
        @else
        <style>
        .nav-action {
          color: var(--ink-primary);
          padding: 0.55rem 1.1rem;
          border: 1px solid var(--border-hairline);
          background-color: var(--surface-white);
          border-radius: 2px;
          transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .nav-action:hover {
          border-color: var(--ink-primary);
        }

        /* Hero Layout */
        .hero {
          max-width: 1380px;
          margin: 0 auto;
          padding: 5.5rem 5% 4.5rem;
        }

        .hero-top-grid {
          display: grid;
          grid-template-columns: 1.35fr 0.85fr;
          gap: 4rem;
          align-items: end;
          padding-bottom: 4.5rem;
          border-bottom: 1px solid var(--border-hairline);
        }

        .kicker {
          display: inline-block;
          font-size: 0.78rem;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.14em;
          color: var(--ink-muted);
          margin-bottom: 1.5rem;
        }

        h1 em {
          font-style: italic;
          color: var(--ink-secondary);
        }

        .hero-right {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          gap: 2rem;
        }

        .lead-copy {
          font-size: 1.075rem;
          color: var(--ink-secondary);
          line-height: 1.7;
          font-weight: 400;
        }

        .cta-row {
          display: flex;
          flex-direction: column;
          gap: 0.9rem;
          align-items: flex-start;
        }

        .btn-primary {
          display: inline-flex;
          align-items: center;
          gap: 0.85rem;
          background-color: var(--accent-dark);
          color: #ffffff;
          padding: 1rem 1.85rem;
          font-size: 0.875rem;
          font-weight: 500;
          letter-spacing: 0.03em;
          text-decoration: none;
          border-radius: 2px;
          transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
          background-color: #1e293b;
          transform: translateY(-1px);
        }

        .mandate-note {
          font-size: 0.8rem;
          color: var(--ink-muted);
          line-height: 1.45;
        }

        /* Practice Areas & Internal Ventures Matrix (Clean Institutional Architecture) */
        .hero-bottom {
          padding-top: 3rem;
          display: grid;
          grid-template-columns: 1.35fr 0.85fr;
          gap: 4rem;
        }

        .section-label {
          font-size: 0.72rem;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.14em;
          color: var(--ink-muted);
          margin-bottom: 1.25rem;
          display: block;
        }

        .capabilities-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 1.25rem 2rem;
        }

        .capability-item {
          padding-top: 0.75rem;
          border-top: 1px solid var(--border-subtle);
        }

        .capability-item h3 {
          font-size: 0.9rem;
          font-weight: 600;
          color: var(--ink-primary);
          margin-bottom: 0.25rem;
        }

        .capability-item p {
          font-size: 0.8rem;
          color: var(--ink-muted);
          line-height: 1.45;
        }

        /* Ventures / R&D Card (Demonstrates skin in the game) */
        .ventures-box {
          background-color: var(--surface-white);
          border: 1px solid var(--border-subtle);
          padding: 1.5rem 1.75rem;
          border-radius: 2px;
        }

        .ventures-box p {
          font-size: 0.85rem;
          color: var(--ink-secondary);
          line-height: 1.6;
          margin-bottom: 1rem;
        }

        .venture-tags {
          display: flex;
          flex-wrap: wrap;
          gap: 0.5rem;
          margin-bottom: 1rem;
        }

        .venture-tag {
          font-size: 0.75rem;
          font-weight: 500;
          color: var(--ink-primary);
          background-color: var(--bg-slate);
          padding: 0.25rem 0.65rem;
          border-radius: 2px;
          border: 1px solid var(--border-subtle);
        }

        .text-link {
          font-size: 0.82rem;
          font-weight: 600;
          color: var(--ink-primary);
          text-decoration: none;
          display: inline-flex;
          align-items: center;
          gap: 0.4rem;
        }

        .text-link:hover {
          text-decoration: underline;
        }

        @media (max-width: 960px) {
          .hero-top-grid,
          .hero-bottom {
            grid-template-columns: 1fr;
            gap: 2.5rem;
          }
          .capabilities-grid {
            grid-template-columns: repeat(2, 1fr);
          }
          nav a:not(.nav-action) {
            display: none;
          }
        }

        @media (max-width: 540px) {
          .capabilities-grid {
            grid-template-columns: 1fr;
          }
        }
      </style>
      <main class="hero">
        <!-- Top Editorial Split -->
        <div class="hero-top-grid">
          <div>
            <span class="kicker">Advisory &bull; Systems &bull; Global Execution</span>
            <h1>
              Move with purpose.<br>
              <em>Complex execution,</em><br>
              delivered cleanly.
            </h1>
          </div>

          <div class="hero-right">
            <p class="lead-copy">
              The world moves fast. We help your business move with precision—strengthening operations, grounding decisions in frontline research, and building the systems and teams required to turn ambitious mandates into reality.
            </p>

            <div class="cta-row">
              <a href="#contact" class="btn-primary">
                Discuss a Mandate <span>&rarr;</span>
              </a>
              <span class="mandate-note">
                We accept a very limited number of client engagements at any time to ensure direct principal involvement.
              </span>
            </div>
          </div>
        </div>

        <!-- Bottom Architecture: 6 Practice Areas + Internal R&D Proof -->
        <div class="hero-bottom">
          <div>
            <span class="section-label">Practice Areas</span>
            <div class="capabilities-grid">
              <div class="capability-item">
                <h3>Operations Advisory</h3>
                <p>Primary research, market intelligence, and structural execution.</p>
              </div>
              <div class="capability-item">
                <h3>Business Systems</h3>
                <p>Bespoke software architecture and internal operational tooling.</p>
              </div>
              <div class="capability-item">
                <h3>Remote Recruitment</h3>
                <p>Individually vetted specialists matched to exact operational briefs.</p>
              </div>
              <div class="capability-item">
                <h3>Brand Identity</h3>
                <p>Institutional positioning, narrative, and visual systems.</p>
              </div>
              <div class="capability-item">
                <h3>Digital Presence</h3>
                <p>High-craft web platforms built for clarity and permanence.</p>
              </div>
              <div class="capability-item">
                <h3>Sales Development</h3>
                <p>Structured market entry and qualified commercial pipelines.</p>
              </div>
            </div>
          </div>

          <div>
            <span class="section-label">Internal Studio &amp; Ventures</span>
            <div class="ventures-box">
              <p>
                Beyond client mandates, we research, architect, and incubate proprietary concepts—from luxury maisons to Rust-based application frameworks.
              </p>
              <div class="venture-tags">
                <span class="venture-tag">Caldezza</span>
                <span class="venture-tag">Feluqa</span>
                <span class="venture-tag">The Best Recruit</span>
                <span class="venture-tag">Virtual Assistant Asia</span>
              </div>
              <a href="#initiatives" class="text-link">
    Utilisez le code avec précaution.
    Explore ideas in development →

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
