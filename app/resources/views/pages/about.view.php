@php
    $about = [
        'intro' => 'Numerimondes is an independent consulting and solutions company based in Casablanca, Morocco. We connect business insight, technology and specialist expertise to explore opportunities, improve operations and turn ideas into practical projects.',
        'about' => 'Numerimondes SARL AU works with business owners, executives and operational leaders who face a specific challenge or opportunity. Each engagement starts with an agreed objective and a clear scope. Our founder leads qualification, solution direction and delivery coordination, bringing in developers, consultants, operators and specialist partners when the work requires them. We remain accountable for the work we undertake.',
        'expertise' => [
            [
                'name' => 'business performance',
                'items' => [
                    'Opportunity and market research',
                    'Commercial strategy and business development',
                    'Operational diagnosis and process improvement',
                    'Project structuring and decision support',
                ],
            ],
            [
                'name' => 'software & automation',
                'items' => [
                    'Custom business applications and internal tools',
                    'Systems integration and automation',
                    'Document workflows and AI-assisted tasks',
                    'Software engineering, including Rust initiatives',
                ],
            ],
            [
                'name' => 'infrastructure & connected operations',
                'items' => [
                    'Infrastructure planning and deployment',
                    'Self-hosted and managed environments',
                    'Equipment integration and monitoring',
                    'Operational continuity and scoped security work',
                ],
            ],
            [
                'name' => 'people & organizations',
                'items' => [
                    'Organizational workflows and coordination',
                    'Reporting and service operations',
                    'Staff training and onboarding',
                    'People-related solutions as scope and readiness allow',
                ],
            ],
            [
                'name' => 'research & ventures',
                'items' => [
                    'Technical and commercial feasibility',
                    'Focused prototypes and experiments',
                    'New product, service and partnership concepts',
                    'Evidence-led decisions on what to pursue next',
                ],
            ],
        ],
        'team' => [
            [
                'name' => 'Yassine El Moumen',
                'role' => 'Founder & Managing Director',
                'bio' => 'His experience brings together operational management, project delivery, training, consulting and software engineering. Research into technology, markets and how organizations work informs a practical approach grounded in business realities.',
                'image' => 'about/founder.jpeg',
            ],
        ],
        'profile' => [
            'positioning' => 'Founder-led. Delivered by a dedicated team shaped around your goals.',
            'technical_skills' => [
                ['Programming', 'Rust, PHP, Go, Bash, SQL'],
                ['Databases', 'SQLite, PostgreSQL, ClickHouse, MySQL, MariaDB, MongoDB'],
                ['Environments', 'Linux (Debian and Arch), Node.js, self-hosted and air-gapped architectures'],
                ['Markup and styling', 'HTML, XML, CSS, Markdown, LaTeX, SVG'],
                ['Tools and industrialization', 'Git, VitePress, Rsync, automation CLI, CI/CD'],
            ],
            'operational_experience' => [
                ['Operations and quality', 'Call-center project management, quality monitoring, training, recruitment, crisis management and debt recovery (Club Med / Thomas Cook).'],
                ['Training', 'Implementation of online training programs.'],
                ['Digital transformation', 'Business support, search engine optimization (SEO) and web development.'],
                ['Dossier structuring', 'Training engineering, public tender responses for Sidi Bou Othmane projects, and pitch decks for fundraising in hospitality and green hydrogen.'],
                ['Management control', 'Business intelligence work including real-time fraud detection in external BPO training centers, and optimizing mission dispatch for a fleet of more than 150 trainers.'],
                ['Engineering', 'Software engineering training; design of Webkernel, a sovereign application platform; and server administration.'],
            ],
            'background' => [
                ['Education', 'Law studies at Université Ibn Zohr; bachelor’s degree not completed.'],
                ['Distinction', 'First Prize in Oratory, HEM Casablanca, 2018.'],
                ['Working languages', 'French, English, Arabic, Darija and written Spanish and Italian.'],
            ],
            'interests' => 'Outside work, Yassine writes short stories and essays, reads poetry and books, and enjoys cycling alone. He also values meaningful conversations, discovering how things work and solving problems.',
        ],
    ];

    $resolveAssets = static function (&$value) use (&$resolveAssets): void {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $resolveAssets($item);
            }
            unset($item);
            return;
        }

        if (is_string($value) && preg_match('/\.(?:webp|svg|jpe?g|png)$/D', $value) === 1) {
            $value = simple_site('studio', $value);
        }
    };

    $resolveAssets($about);
    unset($resolveAssets);
@endphp

<x-studio::page
    class="bleed-page"
    title="About — Numerimondes"
    description="Founder-led consulting and solutions delivered by small specialist teams in Casablanca, Morocco."
>
    <style>
        .nm-about-intro {
            height: 100vh;
            min-height: 600px;
            padding: 90px 50px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fffcf3;
            text-align: center;
            background-color: #222;
            background-image: var(--nm-about-hero-image);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .nm-about-intro h1 {
            max-width: 550px;
            margin: 0;
            font: 400 16px/1.63 var(--nm-title);
            letter-spacing: .19em;
            text-transform: uppercase;
        }
        .nm-about-intro p {
            max-width: 550px;
            margin: 30px auto 0;
            font: 400 18px/1.22 var(--nm-body);
        }
        .nm-expertise-grid { display: grid; grid-template-columns: 1fr; gap: 50px 30px; }
        .nm-expertise-grid article { display: flex; flex-direction: column; gap: 30px; }
        .nm-expertise-grid h3 {
            margin: 0;
            font: 400 16px/1.19 var(--nm-title);
            letter-spacing: .19em;
            text-transform: uppercase;
        }
        .nm-expertise-grid ul { margin: 0; padding: 0; list-style: none; }
        .nm-expertise-grid li { margin: 0; color: var(--nm-ink); font: 400 18px/1.22 var(--nm-body); }
        .nm-about-page .nm-team { padding: 0 50px 70px; }
        .nm-founder-profile { min-width: 0; }
        .nm-founder-profile h2 { margin: 0 0 2rem; }
        .nm-founder-profile img {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 1;
            object-fit: cover;
            object-position: center 35%;
        }
        .nm-founder-profile h3 {
            margin: 20px 0 7px;
            font: 400 26px/1.2 Romie, Georgia, serif;
            letter-spacing: normal;
            text-transform: none;
        }
        .nm-founder-role {
            margin: 0 0 18px;
            color: #8b8881;
            font: 400 10px/1.4 BrownLLMain, Arial, sans-serif;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .nm-founder-bio {
            max-width: 540px;
            margin: 0 0 16px;
            font: 400 16px/1.45 BrownLLMain, Arial, sans-serif;
        }
        .nm-founder-positioning {
            max-width: 480px;
            margin: 26px 0 0;
            color: #77746e;
            font: 400 15px/1.4 BrownLLMain, Arial, sans-serif;
        }
        .nm-founder-details { min-width: 0; display: grid; gap: 14px; }
        .nm-founder-section.fi-collapsed > .fi-section-content-ctn { display: none; }
        .nm-founder-section .fi-section-header-heading { font-size: 11px; line-height: 1.4; }
        .nm-founder-section .fi-section-content-ctn > .fi-section-content,
        .nm-founder-section .fi-section-content,
        .nm-founder-section .nm-founder-facts,
        .nm-founder-section .nm-founder-fact { font-size: 13px; line-height: 1.45; }
        .nm-founder-section .fi-section-header-text-ctn { margin-top: .35rem; }
        .nm-founder-facts { display: grid; }
        .nm-founder-fact {
            display: grid;
            grid-template-columns: minmax(125px, .36fr) minmax(0, 1fr);
            gap: 18px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-default, var(--nm-line));
        }
        .nm-founder-fact:first-child { padding-top: 0; }
        .nm-founder-fact:last-child { padding-bottom: 0; border-bottom: 0; }
        .nm-founder-fact dt { font: inherit; font-weight: 600; }
        .nm-founder-fact dd { margin: 0; font: inherit; }

        @media (max-width: 700px) {
            .nm-about-intro { min-height: 500px; padding: 85px 20px 45px; }
            .nm-about-intro h1 { max-width: 12.49rem; font-size: 12px; }
            .nm-expertise-grid { gap: 35px 20px; }
            .nm-expertise-grid article { gap: 20px; }
            .nm-expertise-grid h3 { font-size: 12px; }
            .nm-expertise-grid li { font-size: 15px; }
            .nm-about-page .nm-team { padding: 0 0 50px; }
            .nm-founder-profile h3 { font-size: 24px; }
            .nm-founder-bio { font-size: 14px; }
            .nm-founder-positioning { margin-top: 20px; font-size: 14px; }
            .nm-founder-fact { grid-template-columns: minmax(100px, .38fr) minmax(0, 1fr); gap: 12px; }
        }
    </style>
    <main class="nm-page nm-about-page">
        <section
            class="nm-about-intro"
            style="--nm-about-hero-image:url('{{ simple_site('studio', 'about/hero.jpg') }}')"
            data-srs-header-tone="light"
        >
            <h1>Independent by design.<br>Curious by nature.</h1>
            <p>{{ $about['intro'] }}</p>
        </section>

        <style>
        .nm-about-p { margin-inline-end: 0 !important; }
        @media (min-width: 640px) { .nm-about-p { margin-inline-end: 2rem !important; } }
        @media (min-width: 768px) { .nm-about-p { margin-inline-end: 5rem !important; } }
        @media (min-width: 1080px) { .nm-about-p { margin-inline-end: 12rem !important; } }
        @media (min-width: 1280px) { .nm-about-p { margin-inline-end: 20rem !important; } }
        </style>
        <section class="nm-about-section">
            <div>
                <h2>Built around the mission</h2>
            </div>
            <div>
                <p class="nm-about-p">{{ $about['about'] }}</p>
            </div>
        </section>

        <section class="nm-about-section">
            <div>
                <h2>Clear aims. Project-specific outcomes.</h2>
            </div>
            <div>
                <p>Expected benefits are assessed for each engagement. Specific results depend on the project and its implementation; examples and visuals on this site are illustrative unless explicitly identified otherwise.</p>
            </div>
        </section>

        <section class="nm-about-section">
            <div>
                <h2>Our expertise</h2>
            </div>
            <div>
                <div class="nm-expertise-grid">
                    @foreach ($about['expertise'] as $service)
                        <article>
                            <h3>{{ $service['name'] }}</h3>
                            <ul>
                                @foreach ($service['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="nm-about-section nm-team">
            <div>
                @foreach ($about['team'] as $person)
                    <article class="nm-founder-profile">
                        <h2>Founder</h2>
                        <img src="{{ $person['image'] }}" alt="{{ $person['name'] }}" width="2163" height="2163" fetchpriority="high">
                        <h3>{{ $person['name'] }}</h3>
                        <p class="nm-founder-role">{{ $person['role'] }}</p>
                        <p class="nm-founder-bio">{{ $person['bio'] }}</p>
                        <p class="nm-founder-positioning">{{ $about['profile']['positioning'] }}</p>
                    </article>
                @endforeach
            </div>
            <div class="nm-founder-details" aria-label="Yassine El Moumen's experience and background">
                <x-webkernel-platform::section heading="Technical skills" icon="heroicon-o-code-bracket" :collapsed="true" :compact="true" class="nm-founder-section">
                    <dl class="nm-founder-facts">
                        @foreach ($about['profile']['technical_skills'] as $skill)
                            <div class="nm-founder-fact"><dt>{{ $skill[0] }}</dt><dd>{{ $skill[1] }}</dd></div>
                        @endforeach
                    </dl>
                </x-webkernel-platform::section>
                <x-webkernel-platform::section heading="Operational experience and expertise" icon="heroicon-o-briefcase" :collapsed="true" :compact="true" class="nm-founder-section">
                    <dl class="nm-founder-facts">
                        @foreach ($about['profile']['operational_experience'] as $experience)
                            <div class="nm-founder-fact"><dt>{{ $experience[0] }}</dt><dd>{{ $experience[1] }}</dd></div>
                        @endforeach
                    </dl>
                </x-webkernel-platform::section>
                <x-webkernel-platform::section heading="Background and languages" icon="heroicon-o-academic-cap" :collapsed="true" :compact="true" class="nm-founder-section">
                    <dl class="nm-founder-facts">
                        @foreach ($about['profile']['background'] as $item)
                            <div class="nm-founder-fact"><dt>{{ $item[0] }}</dt><dd>{{ $item[1] }}</dd></div>
                        @endforeach
                    </dl>
                </x-webkernel-platform::section>
                <x-webkernel-platform::section heading="Outside work" icon="heroicon-o-heart" :collapsed="true" :compact="true" class="nm-founder-section">
                    <p>{{ $about['profile']['interests'] }}</p>
                </x-webkernel-platform::section>
            </div>
        </section>
    </main>
</x-studio::page>
