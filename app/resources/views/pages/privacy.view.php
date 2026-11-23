@props(['canonical' => '', 'locale' => 'en'])
@php
    $studioConfig = config('nm-studio', []);
    $company = $studioConfig['company'] ?? [];
    $contact = $studioConfig['contact'] ?? [];
    $policyPath = base_path('app/resources/views/content/privacy.en.md');
    $policyContent = file_get_contents($policyPath);

    if (! is_string($policyContent)) {
        throw new RuntimeException('Privacy page content could not be read.');
    }

    $policyContent = strtr($policyContent, [
        '[[company_name]]' => (string) ($company['name'] ?? 'Numerimondes'),
        '[[company_location]]' => (string) ($company['location'] ?? 'Casablanca, Morocco'),
        '[[company_phone]]' => (string) ($contact['phone']['display'] ?? ''),
    ]);
@endphp

<x-studio::page
    title="Privacy Policy — Numerimondes"
    description="Privacy and data handling on the public Numerimondes website."
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
>
    <style>
        .nm-policy {
            --policy-top:180px; --policy-bottom:120px; --policy-title-size:16px;
            --policy-body-size:18px; --policy-heading-gap:52px;
        }
        .nm-policy .markdown-heading {
            margin:0 0 24px;
            color:var(--nm-ink);
            font:400 var(--policy-title-size)/1.63 var(--nm-title);
            letter-spacing:.19em;
            text-transform:uppercase;
        }
        .nm-policy .markdown-heading:not(:first-child) {
            margin-top:var(--policy-heading-gap);
        }
        .nm-policy p {
            margin:0 0 15px;
            color:var(--nm-ink);
            font:400 var(--policy-body-size)/1.22 var(--nm-body);
        }
        .nm-policy a { text-decoration:underline; text-underline-offset:.18em; }
        @media (max-width:700px) {
            .nm-policy {
                --policy-top:130px; --policy-bottom:75px; --policy-title-size:12px;
                --policy-body-size:15px; --policy-heading-gap:38px;
            }
        }
    </style>
    <main class="nm-page nm-policy" style="max-width:1040px;margin:0 auto;padding-block:var(--policy-top) var(--policy-bottom)">
        @markdown($policyContent)
    </main>
</x-studio::page>
