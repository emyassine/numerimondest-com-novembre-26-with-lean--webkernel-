@props(['canonical' => '', 'locale' => 'en', 'site_ui' => []])
@php
    $policyPath = base_path('app/resources/views/content/pricing-policy.en.md');
    $policyContent = file_get_contents($policyPath);

    if (! is_string($policyContent)) {
        throw new RuntimeException('Pricing policy content could not be read.');
    }
@endphp

<x-studio::page
    title="Pricing & Engagement Policy — Numerimondes"
    description="How Numerimondes scopes services, sets fees, structures payments and handles indicative currency conversions."
    robots="index,follow"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
>
    <style>
        .nm-policy { --policy-top:180px; --policy-bottom:120px; --policy-title-size:16px; --policy-body-size:18px; --policy-heading-gap:52px; }
        .nm-policy .markdown-heading { margin:0 0 24px; color:var(--nm-ink); font:400 var(--policy-title-size)/1.63 var(--nm-title); letter-spacing:.19em; text-transform:uppercase; }
        .nm-policy .markdown-heading:not(:first-child) { margin-top:var(--policy-heading-gap); }
        .nm-policy p,.nm-policy li { color:var(--nm-ink); font:400 var(--policy-body-size)/1.35 var(--nm-body); }
        .nm-policy p { margin:0 0 15px; }
        .nm-policy a { text-decoration:underline; text-underline-offset:.18em; }
        @media (max-width:700px) { .nm-policy { --policy-top:130px; --policy-bottom:75px; --policy-title-size:12px; --policy-body-size:15px; --policy-heading-gap:38px; } }
    </style>
    <main class="nm-page nm-policy" style="max-width:1040px;margin:0 auto;padding-block:var(--policy-top) var(--policy-bottom)">
        @markdown($policyContent)
    </main>
</x-studio::page>
