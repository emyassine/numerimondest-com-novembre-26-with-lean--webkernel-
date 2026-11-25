<?php declare(strict_types=1);

/** Published seed catalogue. Editable revisions are stored in the offerings database. */
return array (
  0 => 
  array (
    'name' => 
    array (
      'en' => 'Workflow automation sprint',
    ),
    'category' => 'Business systems',
    'format' => '3–5 workflows · scoped build',
    'status' => 'Retired',
    'image' => '/services/infrastructure-before.jpg',
    'image_alt' => 'Connected network equipment supporting automated workflows',
    'image_source' => 'services/infrastructure-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'automotive',
      2 => 'retail',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'technology',
      6 => 'agribusiness',
      7 => 'construction-infrastructure',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'transformation',
    ),
    'id' => 'automation-sprint',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Choose repeatable steps that consume time or create handoff gaps. We design and build a small set of automations around your existing tools.

Typical workflow scope
• Three to five workflows agreed for the sprint.
• Examples include enquiry-to-CRM, onboarding steps, weekly reports and invoice reminders.
• Testing and handover documentation for the agreed workflows.

Delivery
Tool access, workflow complexity, testing and handover requirements are confirmed in a written proposal.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'automation-sprint-base',
        'amount_mad' => 20000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Automate three to five repeatable workflows across your existing tools, with clear handover for your team.',
    ),
  ),
  1 => 
  array (
    'name' => 
    array (
      'en' => 'Brand Book',
    ),
    'category' => 'Brand identity',
    'format' => '4–5 weeks · extended identity system',
    'status' => 'Retired',
    'image' => '/services/business-before.jpg',
    'image_alt' => 'A team mapping a business journey on a planning board',
    'image_source' => 'services/business-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'private-equity',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'advanced-manufacturing-services',
      4 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'transformation',
      2 => 'change-management',
    ),
    'id' => 'brand-book',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Extend the complete identity into a documented system your team can use to keep brand decisions consistent as the business grows.

Included
• Everything in Brand Kit Complete.
• A 40 to 60 page brand book.
• Logo family and social templates.
• Lead magnet design.

Delivery
The stated delivery window is 4 to 5 weeks. Final scope and schedule are confirmed in a written proposal.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-book-base',
        'amount_mad' => 39000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A complete brand system documented for consistent use across your team, channels and future work.',
    ),
  ),
  2 => 
  array (
    'id' => 'brand-core',
    'category' => 'Brand identity & doctrine',
    'format' => 'Foundation',
    'name' => 
    array (
      'en' => 'Core Identity Mandate',
    ),
    'deliverables' => 'Logo system · typography · palette · production files · usage guide',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
    ),
    'business_needs' => 
    array (
      0 => 'customer-experience',
      1 => 'market-expansion',
      2 => 'innovation',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Visual foundations to launch or structure a serious business, without excess.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-core-base',
        'amount_mad' => 24000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'brand',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Visual foundations to launch or structure a serious business, without excess.',
    ),
    'image' => '/services/innovation-after.jpg',
    'image_alt' => 'A shared workspace with laptops and notes',
    'image_source' => 'services/innovation-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  3 => 
  array (
    'id' => 'brand-doctrine',
    'category' => 'Brand identity & doctrine',
    'format' => 'Institutional',
    'name' => 
    array (
      'en' => 'Institutional Brand Doctrine',
    ),
    'deliverables' => 'Doctrine · brand book · governance model · multi-entity rollout',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'private-equity',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'advanced-manufacturing-services',
      4 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'transformation',
      2 => 'change-management',
      3 => 'market-expansion',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'End-to-end brand governance, strategic doctrine and a documented system for groups and houses.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-brand-multi',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-doctrine-base',
        'amount_mad' => 85000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'brand',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'End-to-end brand governance, strategic doctrine and a documented system for groups and houses.',
    ),
    'image' => '/services/innovation-after.jpg',
    'image_alt' => 'A shared workspace with laptops and notes',
    'image_source' => 'services/innovation-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  4 => 
  array (
    'name' => 
    array (
      'en' => 'Brand Kit · Complete',
    ),
    'category' => 'Brand identity',
    'format' => '12–15 working days · complete identity package',
    'status' => 'Retired',
    'image' => '/services/innovation-after.jpg',
    'image_alt' => 'A shared workspace with laptops and notes',
    'image_source' => 'services/innovation-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'customer-experience',
      1 => 'digital-strategy',
      2 => 'market-expansion',
      3 => 'sales-marketing',
    ),
    'id' => 'brand-kit-complete',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Build on a core identity with audience and brand foundations, a practical voice guide and visual applications across your chosen channels.

Included
• Everything in Brand Kit Essential.
• Audience and brand foundation.
• Voice guide and social snapshot.
• Channel dressing and five mockups.

Delivery
The stated delivery window is 12 to 15 working days. Final scope and schedule are confirmed in a written proposal.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'brand-book',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-kit-complete-base',
        'amount_mad' => 18000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A complete visual identity with brand foundations, a voice guide, social direction and channel-ready assets.',
    ),
  ),
  5 => 
  array (
    'name' => 
    array (
      'en' => 'Brand Kit · Essential',
    ),
    'category' => 'Brand identity',
    'format' => '7–10 working days · defined identity package',
    'status' => 'Retired',
    'image' => '/services/innovation-before.jpg',
    'image_alt' => 'A business leader representing a considered brand identity',
    'image_source' => 'services/innovation-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'customer-experience',
      1 => 'market-expansion',
    ),
    'id' => 'brand-kit-essential',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Establish a clear, production-ready visual identity with the core files and guidance your team needs to apply it consistently.

Included
• Primary and alternate logo.
• Colour and typography system.
• Production-ready file pack.
• One-page usage guide.
• Three mockups.

Delivery
The stated delivery window is 7 to 10 working days. Final scope and schedule are confirmed in a written proposal.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'brand-kit-complete',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-kit-essential-base',
        'amount_mad' => 12000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A usable core identity with a logo family, colour and type system, production files and a concise usage guide.',
    ),
  ),
  6 => 
  array (
    'id' => 'brand-system',
    'category' => 'Brand identity & doctrine',
    'format' => 'System',
    'name' => 
    array (
      'en' => 'Brand System & Verbal Architecture',
    ),
    'deliverables' => 'Full identity · verbal territory · templates · usage kit',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'customer-experience',
      1 => 'digital-strategy',
      2 => 'market-expansion',
      3 => 'sales-marketing',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'A complete identity, editorial voice and operating assets, ready for teams to apply.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-brand-kit',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'brand-system-base',
        'amount_mad' => 48000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'brand',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A complete identity, editorial voice and operating assets, ready for teams to apply.',
    ),
    'image' => '/services/innovation-after.jpg',
    'image_alt' => 'A shared workspace with laptops and notes',
    'image_source' => 'services/innovation-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  7 => 
  array (
    'name' => 
    array (
      'en' => 'CRM & client portal setup',
    ),
    'category' => 'Business systems',
    'format' => 'CRM configuration · written project scope',
    'status' => 'Retired',
    'image' => '/services/software-after.jpg',
    'image_alt' => 'A laptop displaying business analytics',
    'image_source' => 'services/software-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'retail',
      1 => 'consumer-products',
      2 => 'financial-services',
      3 => 'technology',
      4 => 'healthcare-life-sciences',
      5 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'sales-marketing',
      1 => 'revenue-growth',
      2 => 'customer-experience',
      3 => 'data-analytics',
    ),
    'id' => 'crm-client-portal',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Set up a practical CRM and client area around the way your organization handles enquiries and serves customers.

Included
• Pipeline stages configured around your process.
• Enquiry and follow-up forms.
• Templates for recurring communication.
• A client area for relevant project information.

A system your team can use
CRM platform, data migration, integrations, access model and handover are agreed in a written project scope.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'crm-client-portal-base',
        'amount_mad' => 25000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A configured pipeline and client area that connect enquiry handling, follow-up and delivery information.',
    ),
  ),
  8 => 
  array (
    'id' => 'dig-cocon',
    'category' => 'Digital presence & search assets',
    'format' => 'Flagship asset',
    'name' => 
    array (
      'en' => 'Search-Engineered Commercial Asset',
    ),
    'deliverables' => 'Semantic cluster · custom backend · expert content · internal and external linking · technical SEO',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'automotive',
      6 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'digital-strategy',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Bespoke architecture, dedicated backend, expert copy and a full semantic cluster (pillar page plus themed satellites), engineered to rank and generate proprietary traffic.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-cocon-10',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-cocon-ml',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      2 => 
      array (
        'offering_id' => 'x-cocon-links',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'dig-cocon-base',
        'amount_mad' => 150000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'digital',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Bespoke architecture, dedicated backend, expert copy and a full semantic cluster (pillar page plus themed satellites), engineered to rank and generate proprietary traffic.',
    ),
    'image' => '/services/software-before.jpg',
    'image_alt' => 'A laptop showing a website code editor',
    'image_source' => 'services/software-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  9 => 
  array (
    'id' => 'dig-flagship',
    'category' => 'Digital presence & search assets',
    'format' => 'Executive',
    'name' => 
    array (
      'en' => 'Executive Flagship',
    ),
    'deliverables' => '5–6 pages · bespoke design · performance engineering · launch',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'financial-services',
      3 => 'healthcare-life-sciences',
      4 => 'technology',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'digital-strategy',
      1 => 'customer-experience',
      2 => 'revenue-growth',
      3 => 'sales-marketing',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'A five-to-six-page institutional presence, delivered in three weeks, to establish credibility immediately.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-flag-bi',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-flag-lead',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'dig-flagship-base',
        'amount_mad' => 45000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'digital',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A five-to-six-page institutional presence, delivered in three weeks, to establish credibility immediately.',
    ),
    'image' => '/services/software-before.jpg',
    'image_alt' => 'A laptop showing a website code editor',
    'image_source' => 'services/software-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  10 => 
  array (
    'id' => 'dig-multi',
    'category' => 'Digital presence & search assets',
    'format' => 'Programme',
    'name' => 
    array (
      'en' => 'Multi-Market Search Authority Programme',
    ),
    'deliverables' => '2+ clusters · multilingual · shared backend · 12-month editorial plan',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'agribusiness',
      6 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'market-expansion',
      1 => 'revenue-growth',
      2 => 'sales-marketing',
      3 => 'digital-strategy',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Coordinated search assets across countries, languages or verticals, with a semantic cluster per market and central editorial governance.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-multi-mkt',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-multi-gov',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'dig-multi-base',
        'amount_mad' => 320000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'digital',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Coordinated search assets across countries, languages or verticals, with a semantic cluster per market and central editorial governance.',
    ),
    'image' => '/services/software-before.jpg',
    'image_alt' => 'A laptop showing a website code editor',
    'image_source' => 'services/software-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  11 => 
  array (
    'id' => 'intel-dd',
    'category' => 'Advisory & research',
    'format' => 'Audit',
    'name' => 
    array (
      'en' => 'Technical & Vendor Due Diligence',
    ),
    'deliverables' => 'Quote review · technical risks · counter-estimate · recommendation',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'private-equity',
      1 => 'financial-services',
      2 => 'technology',
      3 => 'healthcare-life-sciences',
      4 => 'advanced-manufacturing-services',
      5 => 'construction-infrastructure',
      6 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'cost-reduction',
      1 => 'data-analytics',
      2 => 'digital-strategy',
      3 => 'operations',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'An independent review before you sign an expensive quote with an agency or software vendor.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'intel-dd-base',
        'amount_mad' => 14500.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'intel',
    'engagement' => 'One-time',
    'summary' => 
    array (
      'en' => 'An independent review before you sign an expensive quote with an agency or software vendor.',
    ),
    'image' => '/services/business-after.jpg',
    'image_alt' => 'A team reviews a process map in a workshop',
    'image_source' => 'services/business-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  12 => 
  array (
    'id' => 'intel-diag',
    'category' => 'Advisory & research',
    'format' => 'Diagnostic',
    'name' => 
    array (
      'en' => 'Executive Operations Diagnostic',
    ),
    'deliverables' => '90-minute session · written action plan',
    'terms' => 'Fixed fee · due before the session',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'organization',
      2 => 'cost-reduction',
      3 => 'transformation',
      4 => 'revenue-growth',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'A 90-minute working session and a written action plan, credited against any subsequent mandate.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'intel-diag-base',
        'amount_mad' => 4900.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'intel',
    'engagement' => 'One-time',
    'summary' => 
    array (
      'en' => 'A 90-minute working session and a written action plan, credited against any subsequent mandate.',
    ),
    'image' => '/services/business-after.jpg',
    'image_alt' => 'A team reviews a process map in a workshop',
    'image_source' => 'services/business-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  13 => 
  array (
    'id' => 'intel-study',
    'category' => 'Advisory & research',
    'format' => 'Study',
    'name' => 
    array (
      'en' => 'Primary Market & Sector Intelligence Study',
    ),
    'deliverables' => 'Expert interviews · benchmark · decision report · debrief',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'market-expansion',
      1 => 'data-analytics',
      2 => 'innovation',
      3 => 'digital-strategy',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Field research, verified expert interviews and a benchmark ahead of a major investment.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-study-geo',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'intel-study-base',
        'amount_mad' => 65000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'intel',
    'engagement' => 'One-time',
    'summary' => 
    array (
      'en' => 'Field research, verified expert interviews and a benchmark ahead of a major investment.',
    ),
    'image' => '/services/business-after.jpg',
    'image_alt' => 'A team reviews a process map in a workshop',
    'image_source' => 'services/business-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  14 => 
  array (
    'id' => 'mod-auto',
    'name' => 
    array (
      'en' => 'Driving Schools Module',
    ),
    'category' => 'Vertical Software',
    'format' => 'Subscription',
    'type' => 'software',
    'status' => 'waitlist_open',
    'industries' => 
    array (
      0 => 'education-training',
      1 => 'automotive',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'digital-strategy',
    ),
    'description' => 
    array (
      'en' => 'Candidate records, driving-lesson scheduling, exam tracking, payments and automated reminders.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'mod-auto-base',
        'amount_mad' => 690.0,
        'billing' => 'monthly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
      1 => 
      array (
        'id' => 'mod-auto-onboarding',
        'amount_mad' => NULL,
        'billing' => 'one_time',
        'minimum_mad' => 1500,
        'maximum_mad' => 3000,
        'percentage' => NULL,
        'basis' => NULL,
        'label' => 
        array (
          'en' => 'Onboarding and data import',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  15 => 
  array (
    'id' => 'mod-book',
    'name' => 
    array (
      'en' => 'Bookshops & Trade Module',
    ),
    'category' => 'Vertical Software',
    'format' => 'Subscription',
    'type' => 'software',
    'status' => 'waitlist_open',
    'industries' => 
    array (
      0 => 'retail',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'digital-strategy',
    ),
    'description' => 
    array (
      'en' => 'Multi-reference stock, fast checkout, supplier orders and a synchronised online catalogue.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'mod-book-base',
        'amount_mad' => 790.0,
        'billing' => 'monthly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
      1 => 
      array (
        'id' => 'mod-book-onboarding',
        'amount_mad' => NULL,
        'billing' => 'one_time',
        'minimum_mad' => 1500,
        'maximum_mad' => 3000,
        'percentage' => NULL,
        'basis' => NULL,
        'label' => 
        array (
          'en' => 'Onboarding and data import',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  16 => 
  array (
    'id' => 'mod-school',
    'name' => 
    array (
      'en' => 'Private Schools & Training Centres Module',
    ),
    'category' => 'Vertical Software',
    'format' => 'Subscription',
    'type' => 'software',
    'status' => 'waitlist_open',
    'industries' => 
    array (
      0 => 'education-training',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'digital-strategy',
    ),
    'description' => 
    array (
      'en' => 'Enrolment, monthly invoicing, absence tracking, parent portal and communication.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'mod-school-base',
        'amount_mad' => 990.0,
        'billing' => 'monthly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
      1 => 
      array (
        'id' => 'mod-school-onboarding',
        'amount_mad' => NULL,
        'billing' => 'one_time',
        'minimum_mad' => 1500,
        'maximum_mad' => 3000,
        'percentage' => NULL,
        'basis' => NULL,
        'label' => 
        array (
          'en' => 'Onboarding and data import',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  17 => 
  array (
    'name' => 
    array (
      'en' => 'Operations audit',
    ),
    'category' => 'Operations advisory',
    'format' => '90-minute session · written plan',
    'status' => 'Retired',
    'image' => '/services/business-after.jpg',
    'image_alt' => 'A team reviews a process map in a workshop',
    'image_source' => 'services/business-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'organization',
      2 => 'cost-reduction',
      3 => 'transformation',
    ),
    'id' => 'ops-audit',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Bring the recurring work, bottlenecks or loose ends that are taking attention away from your team. The audit turns them into a practical delegation plan.

You receive
• A 90-minute working session focused on your current operations.
• A written plan listing what to hand off first.
• A clear next step for an Executive, Revenue or Brand & Digital service, if relevant.

A practical first step
The audit fee is credited toward a dedicated team you start within 30 days.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'ops-audit-base',
        'amount_mad' => 4900.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'One-time',
    'summary' => 
    array (
      'en' => 'A 90-minute working session that identifies what to delegate first and turns it into a written action plan.',
    ),
  ),
  18 => 
  array (
    'id' => 'pb-brand',
    'name' => 
    array (
      'en' => 'Brand Positioning & Doctrine Blueprint',
    ),
    'target' => 'Companies whose image no longer reflects the real level of their service.',
    'deliverables' => 'Brand platform · competitive positioning · offer architecture · editorial line',
    'category' => 'Executive Playbook',
    'format' => 'Playbook',
    'type' => 'playbook',
    'credit_window_days' => 30,
    'credit_percent' => 100,
    'industries' => 
    array (
      0 => 'private-equity',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'advanced-manufacturing-services',
      4 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'transformation',
      2 => 'change-management',
      3 => 'market-expansion',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'brand-doctrine',
        'type' => 'gateway',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'pb-brand-base',
        'amount_mad' => 18000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  19 => 
  array (
    'id' => 'pb-out',
    'name' => 
    array (
      'en' => 'Outbound & Pipeline Blueprint',
    ),
    'target' => 'B2B firms and practices that sign only through word of mouth.',
    'deliverables' => 'ICP definition · outreach sequences · qualification scripts · CRM structure',
    'category' => 'Executive Playbook',
    'format' => 'Playbook',
    'type' => 'playbook',
    'credit_window_days' => 30,
    'credit_percent' => 100,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'customer-experience',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'sales-desk',
        'type' => 'gateway',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'pb-out-base',
        'amount_mad' => 16500.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  20 => 
  array (
    'id' => 'pb-rem',
    'name' => 
    array (
      'en' => 'Remote Operations & Hiring Blueprint',
    ),
    'target' => 'Leaders who want to hire and manage remotely without chaos.',
    'deliverables' => 'Candidate scorecards · interview grids · onboarding SOPs · weekly operating rhythm',
    'category' => 'Executive Playbook',
    'format' => 'Playbook',
    'type' => 'playbook',
    'credit_window_days' => 30,
    'credit_percent' => 100,
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'operations',
      2 => 'change-management',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'rec-ops',
        'type' => 'gateway',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'pb-rem-base',
        'amount_mad' => 15000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  21 => 
  array (
    'id' => 'pb-saas',
    'name' => 
    array (
      'en' => 'Software & SaaS Scoping Blueprint',
    ),
    'target' => 'Founders and large accounts before committing 300,000 MAD or more to development.',
    'deliverables' => 'Functional architecture · stack choice (Rust, cloud) · data model · costing · roadmap',
    'category' => 'Executive Playbook',
    'format' => 'Playbook',
    'type' => 'playbook',
    'credit_window_days' => 30,
    'credit_percent' => 100,
    'industries' => 
    array (
      0 => 'technology',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'retail',
      4 => 'telecommunications',
      5 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'innovation',
      1 => 'digital-strategy',
      2 => 'data-analytics',
      3 => 'transformation',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'sys-saas',
        'type' => 'gateway',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'pb-saas-base',
        'amount_mad' => 29000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  22 => 
  array (
    'id' => 'pb-sem',
    'name' => 
    array (
      'en' => 'Semantic Authority Blueprint',
    ),
    'target' => 'SMEs and e-commerce brands that want to win on Google without paying for ads indefinitely.',
    'deliverables' => 'Cluster map (1 pillar, 15–25 satellites) · internal linking plan · writing briefs · technical audit',
    'category' => 'Executive Playbook',
    'format' => 'Playbook',
    'type' => 'playbook',
    'credit_window_days' => 30,
    'credit_percent' => 100,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'automotive',
      6 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'digital-strategy',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'dig-cocon',
        'type' => 'gateway',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'pb-sem-base',
        'amount_mad' => 19000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  23 => 
  array (
    'id' => 'rec-exec',
    'category' => 'Talent & recruitment',
    'format' => 'Executive',
    'name' => 
    array (
      'en' => 'The Best Recruit · Executive & Director Search',
    ),
    'deliverables' => 'Market mapping · direct approach · assessed shortlist · onboarding',
    'terms' => 'Due on signature of the hire',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'change-management',
      2 => 'transformation',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Confidential executive search and in-depth assessment of senior leaders.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'rec-exec-base',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'kind' => 'monthly_salary',
          'currency' => 'same_as_salary',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '46% of monthly salary',
        ),
      ),
    ),
    'practice' => 'recruit',
    'engagement' => 'Recruitment mandate',
    'summary' => 
    array (
      'en' => 'Confidential executive search and in-depth assessment of senior leaders.',
    ),
    'image' => '/projects/the-best-recruit.jpg',
    'image_alt' => 'The Best Recruit identity for executive and director search',
    'image_source' => 'projects/the-best-recruit.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  24 => 
  array (
    'id' => 'rec-ops',
    'category' => 'Talent & recruitment',
    'format' => 'Execution & admin',
    'name' => 
    array (
      'en' => 'The Best Recruit · Execution & Admin',
    ),
    'deliverables' => 'Sourcing · assessments · interviews · supported probation',
    'terms' => 'Due on signature of the hire',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'operations',
      2 => 'change-management',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Fast recruitment of qualified operational and support profiles, remote or on site.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'rec-ops-base',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'kind' => 'monthly_salary',
          'currency' => 'same_as_salary',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '46% of monthly salary',
        ),
      ),
    ),
    'practice' => 'recruit',
    'engagement' => 'Recruitment mandate',
    'summary' => 
    array (
      'en' => 'Fast recruitment of qualified operational and support profiles, remote or on site.',
    ),
    'image' => '/projects/the-best-recruit.jpg',
    'image_alt' => 'The Best Recruit identity for executive and director search',
    'image_source' => 'projects/the-best-recruit.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  25 => 
  array (
    'name' => 
    array (
      'en' => 'Revenue Desk · Growth',
    ),
    'category' => 'Sales development',
    'format' => 'Monthly service · one to two channels',
    'status' => 'Retired',
    'image' => '/services/operations-before.jpg',
    'image_alt' => 'Colleagues coordinating work around a table',
    'image_source' => 'services/operations-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
    ),
    'id' => 'revenue-desk-growth',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Keep a consistent, visible outreach rhythm with prospect lists, one or two agreed channels, reply handling, calendar booking and updates to your CRM.

What the monthly service covers
• Prospect research and list preparation.
• Outreach across one or two agreed channels.
• Reply handling and call booking.
• CRM updates and a weekly activity report.

Pricing and fit
Channel mix, prospect volume, tools and the final scope are confirmed in a written proposal.
Numerimondes reports messages sent, replies, conversations started, calls booked and next steps. No number of meetings is guaranteed.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      2 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'revenue-desk-growth-base',
        'amount_mad' => 19900.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Monthly',
    'summary' => 
    array (
      'en' => 'A recurring prospecting and outreach operation with reply handling, call booking and weekly reporting.',
    ),
  ),
  26 => 
  array (
    'name' => 
    array (
      'en' => 'Revenue Desk · 30-day pilot',
    ),
    'category' => 'Sales development',
    'format' => '30 days · one-time pilot',
    'status' => 'Retired',
    'image' => '/services/infrastructure-after.jpg',
    'image_alt' => 'A network switch with connected cables',
    'image_source' => 'services/infrastructure-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
    ),
    'id' => 'revenue-desk-pilot',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'A defined first month to test an outbound sales workflow. We agree the target client, draft and approve the message sequence, prepare the tools and report what happened at the end of the pilot.

Included in the pilot
• One ideal client profile and target market.
• One approved outreach sequence across the agreed channel or channels.
• Research for approximately 500 prospects.
• Outreach, follow-ups and reply handling.
• A final report with results, learnings and a recommendation.

A measured start
The four-week sequence covers kickoff and setup, the first outreach batch, review and refinement, then a final report. Meeting volumes are not guaranteed; activity and outcomes are reported transparently.
Any additional scope or tools are confirmed in the written proposal before work begins.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'revenue-desk-growth',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'revenue-desk-pilot-base',
        'amount_mad' => 9900.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'One-time',
    'summary' => 
    array (
      'en' => 'Test a focused prospecting and outreach workflow with one ideal client profile, one message sequence and a clear report.',
    ),
  ),
  27 => 
  array (
    'name' => 
    array (
      'en' => 'Revenue Desk · Scale',
    ),
    'category' => 'Sales development',
    'format' => 'Monthly service · two to three channels',
    'status' => 'Retired',
    'image' => '/services/operations-after.jpg',
    'image_alt' => 'A team coordinating work around laptops',
    'image_source' => 'services/operations-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
    ),
    'id' => 'revenue-desk-scale',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'For teams ready to coordinate multiple prospecting channels and sequences, with deliverability management, monthly optimization and a strategy review.

What the monthly service covers
• Prospect research and two to three agreed outreach channels.
• Two active message sequences.
• Reply handling, call booking and CRM updates.
• Deliverability management and monthly optimization.
• A monthly strategy call and activity reporting.

Pricing and fit
Final channels, volumes, tooling and scope are confirmed in a written proposal.
The service reports outreach activity and outcomes; it does not guarantee meeting numbers or sales.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      2 => 
      array (
        'offering_id' => 'website-build',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'revenue-desk-scale-base',
        'amount_mad' => 39000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Monthly',
    'summary' => 
    array (
      'en' => 'A broader outbound programme with multiple channels, active sequences and ongoing deliverability management.',
    ),
  ),
  28 => 
  array (
    'id' => 'sales-desk',
    'category' => 'Sales development',
    'format' => 'Programme',
    'name' => 
    array (
      'en' => 'Revenue Desk · 90-Day Programme',
    ),
    'deliverables' => 'Targeting · sequences · appointment setting · weekly reporting · final report',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'customer-experience',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'A dedicated outbound desk that tests your market, builds the sequences and delivers a documented pipeline.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-desk-seg',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-desk-mo',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sales-desk-base',
        'amount_mad' => 59000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'sales',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A dedicated outbound desk that tests your market, builds the sequences and delivers a documented pipeline.',
    ),
    'image' => '/services/infrastructure-after.jpg',
    'image_alt' => 'A network switch with connected cables',
    'image_source' => 'services/infrastructure-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  29 => 
  array (
    'id' => 'sales-force',
    'name' => 
    array (
      'en' => 'Revenue Force · Recruit, Equip, Supervise',
    ),
    'category' => 'Sales development',
    'format' => 'Revenue Force',
    'type' => 'service',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'organization',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sales-force-setup',
        'amount_mad' => 7500.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => 'One-time setup',
        ),
      ),
      1 => 
      array (
        'id' => 'sales-force-supervision',
        'amount_mad' => NULL,
        'billing' => 'monthly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => 4500,
        'maximum_mad' => 6500,
        'label' => 
        array (
          'en' => 'Weekly commercial supervision',
        ),
      ),
      2 => 
      array (
        'id' => 'sales-force-recruitment',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'value_key' => 'monthly_salary',
          'currency_key' => 'salary_currency',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => 'Recruitment fee via The Best Recruit',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  30 => 
  array (
    'id' => 'sys-crm',
    'category' => 'Business systems & software',
    'format' => 'Infrastructure',
    'name' => 
    array (
      'en' => 'CRM & Client Portal Infrastructure',
    ),
    'deliverables' => 'CRM · client portal · roles and access · integrations',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'retail',
      1 => 'consumer-products',
      2 => 'financial-services',
      3 => 'technology',
      4 => 'healthcare-life-sciences',
      5 => 'telecommunications',
      6 => 'automotive',
      7 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'sales-marketing',
      1 => 'revenue-growth',
      2 => 'customer-experience',
      3 => 'data-analytics',
      4 => 'digital-strategy',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'A bespoke sales pipeline and client portal, connected to your existing tools.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-crm-int',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-crm-app',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sys-crm-base',
        'amount_mad' => 65000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'systems',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A bespoke sales pipeline and client portal, connected to your existing tools.',
    ),
    'image' => '/services/software-after.jpg',
    'image_alt' => 'A laptop displaying business analytics',
    'image_source' => 'services/software-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  31 => 
  array (
    'id' => 'sys-ent',
    'category' => 'Business systems & software',
    'format' => 'Enterprise',
    'name' => 
    array (
      'en' => 'Enterprise Data & Platform Programme',
    ),
    'deliverables' => 'Target architecture · platform · IS integrations · security · run',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'data-analytics',
      1 => 'digital-strategy',
      2 => 'operations',
      3 => 'organization',
      4 => 'transformation',
      5 => 'cost-reduction',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'An organisation-scale business platform: data, integrations, security, governance and a dedicated delivery team.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-ent-team',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sys-ent-base',
        'amount_mad' => 520000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'systems',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'An organisation-scale business platform: data, integrations, security, governance and a dedicated delivery team.',
    ),
    'image' => '/services/software-after.jpg',
    'image_alt' => 'A laptop displaying business analytics',
    'image_source' => 'services/software-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  32 => 
  array (
    'id' => 'sys-saas',
    'category' => 'Business systems & software',
    'format' => 'Platform',
    'name' => 
    array (
      'en' => 'Proprietary SaaS & Platform Engineering',
    ),
    'deliverables' => 'Architecture · Rust backend · front end · cloud infrastructure · deployment',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => true,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'technology',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'retail',
      4 => 'telecommunications',
      5 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'innovation',
      1 => 'digital-strategy',
      2 => 'data-analytics',
      3 => 'transformation',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Design, backend architecture (Rust, cloud) and deployment of complete software products.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-saas-admin',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-saas-pay',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      2 => 
      array (
        'offering_id' => 'x-saas-sec',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sys-saas-base',
        'amount_mad' => 280000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'systems',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Design, backend architecture (Rust, cloud) and deployment of complete software products.',
    ),
    'image' => '/services/software-after.jpg',
    'image_alt' => 'A laptop displaying business analytics',
    'image_source' => 'services/software-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  33 => 
  array (
    'id' => 'sys-sprint',
    'category' => 'Business systems & software',
    'format' => 'Sprint',
    'name' => 
    array (
      'en' => 'Operational Workflow Sprint',
    ),
    'deliverables' => 'Process mapping · 3–5 automations · documentation · handover',
    'terms' => 'Fixed fee · 50% at kickoff, 50% on delivery',
    'flagship' => false,
    'type' => 'mandate',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'automotive',
      2 => 'retail',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'technology',
      6 => 'agribusiness',
      7 => 'construction-infrastructure',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'transformation',
      3 => 'change-management',
    ),
    'status' => 'Taking enquiries',
    'description' => 
    array (
      'en' => 'Targeted automation of three to five critical workflows on the tools you already use.',
    ),
    'active' => true,
    'visibility' => 'catalog',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'x-sprint-flow',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
      1 => 
      array (
        'offering_id' => 'x-sprint-train',
        'type' => 'extension',
        'optional' => true,
        'visibility' => 'detail_only',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'sys-sprint-base',
        'amount_mad' => 35000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'practice' => 'systems',
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'Targeted automation of three to five critical workflows on the tools you already use.',
    ),
    'image' => '/services/software-after.jpg',
    'image_alt' => 'A laptop displaying business analytics',
    'image_source' => 'services/software-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
  ),
  34 => 
  array (
    'name' => 
    array (
      'en' => 'The Best Recruit · Executive & Director Search',
    ),
    'category' => 'Remote recruitment',
    'recruitment_category' => 'executive_director',
    'format' => 'Executive and director roles · 46% of monthly salary',
    'status' => 'Retired',
    'image' => '/projects/the-best-recruit.jpg',
    'image_alt' => 'The Best Recruit identity for executive and director search',
    'image_source' => 'projects/the-best-recruit.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
    ),
    'business_needs' => 
    array (
    ),
    'id' => 'the-best-recruit',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'A candidate database and role-specific search for executive and director appointments. Share the position and salary budget to start a search request.

Executive and director appointments
The recruitment fee is 46% of the selected candidate’s monthly salary. The exact fee is calculated from the agreed salary and confirmed in the written terms before the search starts.
The search brief covers responsibilities, experience, location, compensation and timing. Candidate introductions and information are handled with appropriate consent.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'the-best-recruit-base',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'kind' => 'monthly_salary',
          'currency' => 'same_as_salary',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Recruitment mandate',
    'summary' => 
    array (
      'en' => 'A focused search for executive and director roles, priced at 46% of the hired candidate’s monthly salary.',
    ),
  ),
  35 => 
  array (
    'name' => 
    array (
      'en' => 'The Best Recruit · Administrative & Operations',
    ),
    'category' => 'Remote recruitment',
    'recruitment_category' => 'administrative_operations',
    'format' => 'Administrative and operations roles · 46% of monthly salary',
    'status' => 'Retired',
    'image' => '/services/business-after.jpg',
    'image_alt' => 'A team reviewing an administrative workflow',
    'image_source' => 'services/business-after.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
    ),
    'business_needs' => 
    array (
    ),
    'id' => 'the-best-recruit-administrative',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Find administrative and operations talent through a candidate database and a search shaped around your team’s day-to-day needs.

Administrative and operations roles
The recruitment fee is 46% of the selected candidate’s monthly salary. The exact fee is calculated from the agreed salary and confirmed in the written terms before the search starts.
Share the responsibilities, required experience, work location, compensation and hiring timeline. Candidate introductions and information are handled with appropriate consent.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'the-best-recruit-administrative-base',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'kind' => 'monthly_salary',
          'currency' => 'same_as_salary',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Recruitment mandate',
    'summary' => 
    array (
      'en' => 'A role-specific search for administrative and operations professionals, priced at 46% of the hired candidate’s monthly salary.',
    ),
  ),
  36 => 
  array (
    'name' => 
    array (
      'en' => 'The Best Recruit · Execution & Handling',
    ),
    'category' => 'Remote recruitment',
    'recruitment_category' => 'execution_handling',
    'format' => 'Handling and execution roles · 46% of monthly salary',
    'status' => 'Retired',
    'image' => '/services/operations-before.jpg',
    'image_alt' => 'Operations staff coordinating their work',
    'image_source' => 'services/operations-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
    ),
    'business_needs' => 
    array (
    ),
    'id' => 'the-best-recruit-execution',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Source handling and execution personnel through a candidate database, with selection criteria agreed for the position and workplace.

Handling and execution roles
The recruitment fee is 46% of the selected candidate’s monthly salary. The exact fee is calculated from the agreed salary and confirmed in the written terms before the search starts.
Share the duties, experience and certifications required, work location, compensation and hiring timeline. Candidate introductions and information are handled with appropriate consent.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'the-best-recruit-execution-base',
        'amount_mad' => NULL,
        'billing' => 'percentage',
        'percentage' => 46.0,
        'basis' => 
        array (
          'kind' => 'monthly_salary',
          'currency' => 'same_as_salary',
        ),
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Recruitment mandate',
    'summary' => 
    array (
      'en' => 'A practical search for handling and execution roles, priced at 46% of the hired candidate’s monthly salary.',
    ),
  ),
  37 => 
  array (
    'name' => 
    array (
      'en' => 'Business website',
    ),
    'category' => 'Digital presence',
    'format' => '5–6 pages · 3–4 weeks',
    'status' => 'Retired',
    'image' => '/services/software-before.jpg',
    'image_alt' => 'A laptop showing a website code editor',
    'image_source' => 'services/software-before.jpg',
    'image_width' => 1200,
    'image_height' => 800,
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'financial-services',
      3 => 'healthcare-life-sciences',
      4 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'digital-strategy',
      1 => 'customer-experience',
      2 => 'revenue-growth',
    ),
    'id' => 'website-build',
    'type' => 'service',
    'description' => 
    array (
      'en' => 'Create a clear digital presence around your offer, with connected enquiry flow, analytics setup and launch checks.

Included
• A five to six page website and offer pages.
• A connected lead magnet.
• Analytics setup.
• Launch checks.

Delivery
The stated delivery window is 3 to 4 weeks. Content, integrations, revisions and final schedule are confirmed in a written proposal.',
    ),
    'active' => false,
    'visibility' => 'hidden',
    'relations' => 
    array (
      0 => 
      array (
        'offering_id' => 'brand-kit-complete',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      1 => 
      array (
        'offering_id' => 'crm-client-portal',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
      2 => 
      array (
        'offering_id' => 'automation-sprint',
        'type' => 'complementary',
        'optional' => true,
        'visibility' => 'catalog',
      ),
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'website-build-base',
        'amount_mad' => 25000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
    'summary' => 
    array (
      'en' => 'A focused five to six page website that explains your offer, connects enquiries and is checked for launch.',
    ),
  ),
  38 => 
  array (
    'id' => 'x-brand-kit',
    'name' => 
    array (
      'en' => 'Social and motion template kit',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Brand System & Verbal Architecture',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'customer-experience',
      1 => 'digital-strategy',
      2 => 'market-expansion',
      3 => 'sales-marketing',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-brand-kit-base',
        'amount_mad' => 12000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  39 => 
  array (
    'id' => 'x-brand-multi',
    'name' => 
    array (
      'en' => 'Multi-entity brand architecture',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Institutional Brand Doctrine',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'private-equity',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'advanced-manufacturing-services',
      4 => 'technology',
    ),
    'business_needs' => 
    array (
      0 => 'organization',
      1 => 'transformation',
      2 => 'change-management',
      3 => 'market-expansion',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-brand-multi-base',
        'amount_mad' => 30000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  40 => 
  array (
    'id' => 'x-cocon-10',
    'name' => 
    array (
      'en' => '10-page expert cluster',
    ),
    'description' => 
    array (
      'en' => 'A new themed cluster to capture an additional vertical.',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Search-Engineered Commercial Asset',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'automotive',
      6 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-cocon-10-base',
        'amount_mad' => 25000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  41 => 
  array (
    'id' => 'x-cocon-links',
    'name' => 
    array (
      'en' => 'Authority link-building programme',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Search-Engineered Commercial Asset',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'automotive',
      6 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-cocon-links-base',
        'amount_mad' => 35000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  42 => 
  array (
    'id' => 'x-cocon-ml',
    'name' => 
    array (
      'en' => 'Multilingual pack',
    ),
    'description' => 
    array (
      'en' => 'Cluster localised into a second language.',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Search-Engineered Commercial Asset',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'automotive',
      6 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-cocon-ml-base',
        'amount_mad' => 40000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  43 => 
  array (
    'id' => 'x-crm-app',
    'name' => 
    array (
      'en' => 'White-label client mobile app',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · CRM & Client Portal Infrastructure',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'retail',
      1 => 'consumer-products',
      2 => 'financial-services',
      3 => 'technology',
      4 => 'healthcare-life-sciences',
      5 => 'telecommunications',
      6 => 'automotive',
      7 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'sales-marketing',
      1 => 'revenue-growth',
      2 => 'customer-experience',
      3 => 'data-analytics',
      4 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-crm-app-base',
        'amount_mad' => 55000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  44 => 
  array (
    'id' => 'x-crm-int',
    'name' => 
    array (
      'en' => 'Additional system integration',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · CRM & Client Portal Infrastructure',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'retail',
      1 => 'consumer-products',
      2 => 'financial-services',
      3 => 'technology',
      4 => 'healthcare-life-sciences',
      5 => 'telecommunications',
      6 => 'automotive',
      7 => 'aviation',
    ),
    'business_needs' => 
    array (
      0 => 'sales-marketing',
      1 => 'revenue-growth',
      2 => 'customer-experience',
      3 => 'data-analytics',
      4 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-crm-int-base',
        'amount_mad' => 12000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  45 => 
  array (
    'id' => 'x-desk-mo',
    'name' => 
    array (
      'en' => 'Continue as a monthly desk',
    ),
    'description' => 
    array (
      'en' => 'Billed monthly after the programme.',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Revenue Desk · 90-Day Programme',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'customer-experience',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-desk-mo-base',
        'amount_mad' => 19900.0,
        'billing' => 'monthly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  46 => 
  array (
    'id' => 'x-desk-seg',
    'name' => 
    array (
      'en' => 'Additional target segment',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Revenue Desk · 90-Day Programme',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'advanced-manufacturing-services',
      5 => 'agribusiness',
      6 => 'healthcare-life-sciences',
      7 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'revenue-growth',
      1 => 'sales-marketing',
      2 => 'market-expansion',
      3 => 'customer-experience',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-desk-seg-base',
        'amount_mad' => 20000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  47 => 
  array (
    'id' => 'x-ent-team',
    'name' => 
    array (
      'en' => 'Dedicated delivery team, per quarter',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Enterprise Data & Platform Programme',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'data-analytics',
      1 => 'digital-strategy',
      2 => 'operations',
      3 => 'organization',
      4 => 'transformation',
      5 => 'cost-reduction',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-ent-team-base',
        'amount_mad' => 150000.0,
        'billing' => 'quarterly',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
        'unit' => 'quarter',
      ),
    ),
    'engagement' => 'Project',
  ),
  48 => 
  array (
    'id' => 'x-flag-bi',
    'name' => 
    array (
      'en' => 'Arabic and English bilingual build',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Executive Flagship',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'financial-services',
      3 => 'healthcare-life-sciences',
      4 => 'technology',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'digital-strategy',
      1 => 'customer-experience',
      2 => 'revenue-growth',
      3 => 'sales-marketing',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-flag-bi-base',
        'amount_mad' => 12000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  49 => 
  array (
    'id' => 'x-flag-lead',
    'name' => 
    array (
      'en' => 'Booking and lead-capture integration',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Executive Flagship',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'financial-services',
      3 => 'healthcare-life-sciences',
      4 => 'technology',
      5 => 'media-entertainment',
    ),
    'business_needs' => 
    array (
      0 => 'digital-strategy',
      1 => 'customer-experience',
      2 => 'revenue-growth',
      3 => 'sales-marketing',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-flag-lead-base',
        'amount_mad' => 9000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  50 => 
  array (
    'id' => 'x-multi-gov',
    'name' => 
    array (
      'en' => '12-month editorial governance',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Multi-Market Search Authority Programme',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'agribusiness',
      6 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'market-expansion',
      1 => 'revenue-growth',
      2 => 'sales-marketing',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-multi-gov-base',
        'amount_mad' => 60000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  51 => 
  array (
    'id' => 'x-multi-mkt',
    'name' => 
    array (
      'en' => 'Additional market cluster',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Multi-Market Search Authority Programme',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'consumer-products',
      1 => 'retail',
      2 => 'technology',
      3 => 'financial-services',
      4 => 'healthcare-life-sciences',
      5 => 'agribusiness',
      6 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'market-expansion',
      1 => 'revenue-growth',
      2 => 'sales-marketing',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-multi-mkt-base',
        'amount_mad' => 90000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  52 => 
  array (
    'id' => 'x-saas-admin',
    'name' => 
    array (
      'en' => 'Admin and analytics console',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Proprietary SaaS & Platform Engineering',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'technology',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'retail',
      4 => 'telecommunications',
      5 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'innovation',
      1 => 'digital-strategy',
      2 => 'data-analytics',
      3 => 'transformation',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-saas-admin-base',
        'amount_mad' => 45000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  53 => 
  array (
    'id' => 'x-saas-pay',
    'name' => 
    array (
      'en' => 'Payments and subscription billing',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Proprietary SaaS & Platform Engineering',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'technology',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'retail',
      4 => 'telecommunications',
      5 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'innovation',
      1 => 'digital-strategy',
      2 => 'data-analytics',
      3 => 'transformation',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-saas-pay-base',
        'amount_mad' => 30000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  54 => 
  array (
    'id' => 'x-saas-sec',
    'name' => 
    array (
      'en' => 'Security and penetration review',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Proprietary SaaS & Platform Engineering',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'technology',
      1 => 'financial-services',
      2 => 'healthcare-life-sciences',
      3 => 'retail',
      4 => 'telecommunications',
      5 => 'advanced-manufacturing-services',
    ),
    'business_needs' => 
    array (
      0 => 'innovation',
      1 => 'digital-strategy',
      2 => 'data-analytics',
      3 => 'transformation',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-saas-sec-base',
        'amount_mad' => 25000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  55 => 
  array (
    'id' => 'x-sprint-flow',
    'name' => 
    array (
      'en' => 'Additional workflow automation',
    ),
    'description' => 
    array (
      'en' => 'Per workflow.',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Operational Workflow Sprint',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'automotive',
      2 => 'retail',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'technology',
      6 => 'agribusiness',
      7 => 'construction-infrastructure',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'transformation',
      3 => 'change-management',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-sprint-flow-base',
        'amount_mad' => 9000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
        'unit' => 'workflow',
      ),
    ),
    'engagement' => 'Project',
  ),
  56 => 
  array (
    'id' => 'x-sprint-train',
    'name' => 
    array (
      'en' => 'Team onboarding and training',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Operational Workflow Sprint',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'automotive',
      2 => 'retail',
      3 => 'healthcare-life-sciences',
      4 => 'financial-services',
      5 => 'technology',
      6 => 'agribusiness',
      7 => 'construction-infrastructure',
    ),
    'business_needs' => 
    array (
      0 => 'operations',
      1 => 'cost-reduction',
      2 => 'transformation',
      3 => 'change-management',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-sprint-train-base',
        'amount_mad' => 6000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
  57 => 
  array (
    'id' => 'x-study-geo',
    'name' => 
    array (
      'en' => 'Additional sector or geography',
    ),
    'description' => 
    array (
      'en' => '',
    ),
    'visibility' => 'detail_only',
    'type' => 'service',
    'category' => 'Extension · Primary Market & Sector Intelligence Study',
    'format' => 'Extension',
    'industries' => 
    array (
      0 => 'advanced-manufacturing-services',
      1 => 'aerospace-defense',
      2 => 'agribusiness',
      3 => 'automotive',
      4 => 'aviation',
      5 => 'chemicals',
      6 => 'construction-infrastructure',
      7 => 'consumer-products',
      8 => 'energy-natural-resources',
      9 => 'financial-services',
      10 => 'healthcare-life-sciences',
      11 => 'machinery-equipment',
      12 => 'media-entertainment',
      13 => 'mining',
      14 => 'oil-gas',
      15 => 'paper-packaging',
      16 => 'private-equity',
      17 => 'retail',
      18 => 'social-impact',
      19 => 'technology',
      20 => 'telecommunications',
    ),
    'business_needs' => 
    array (
      0 => 'market-expansion',
      1 => 'data-analytics',
      2 => 'innovation',
      3 => 'digital-strategy',
    ),
    'active' => true,
    'relations' => 
    array (
    ),
    'prices' => 
    array (
      0 => 
      array (
        'id' => 'x-study-geo-base',
        'amount_mad' => 30000.0,
        'billing' => 'one_time',
        'percentage' => NULL,
        'basis' => NULL,
        'minimum_mad' => NULL,
        'maximum_mad' => NULL,
        'label' => 
        array (
          'en' => '',
        ),
      ),
    ),
    'engagement' => 'Project',
  ),
);
