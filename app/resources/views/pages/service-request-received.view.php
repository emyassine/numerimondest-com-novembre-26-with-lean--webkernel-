@props(['canonical' => '', 'locale' => 'en'])

<x-studio::page
    title="Private request received — Numerimondes"
    description="Your private request has reached Numerimondes for personal follow-up."
    robots="noindex,follow"
    rss="false"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
>
    <style>
        .nm-service-request-received { min-height:70vh; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
        .nm-service-request-received h1 { margin:0; font:400 16px/1.5 var(--nm-title); letter-spacing:.18em; text-transform:uppercase; }
        .nm-service-request-kicker { margin:0 0 18px; color:#77746e; font:400 10px/1.35 var(--nm-title); letter-spacing:.15em; text-transform:uppercase; }
        .nm-service-request-received p { max-width:580px; margin:25px 0 0; font:400 17px/1.45 var(--nm-body); }
        .nm-service-request-received a { margin-top:30px; color:var(--primary-600); font:400 10px/1.4 var(--nm-title); letter-spacing:.15em; text-decoration:none; text-transform:uppercase; border-bottom:1px solid currentColor; padding-bottom:8px; }
        @media (max-width:700px) {
            .nm-service-request-received { min-height:65vh; }
            .nm-service-request-received h1 { font-size:12px; }
            .nm-service-request-kicker { font-size:8px; }
            .nm-service-request-received p { font-size:15px; }
            .nm-service-request-received a { font-size:8px; }
        }
    </style>
    <main class="nm-page nm-service-request-received">
        <p class="nm-service-request-kicker">Request received</p>
        <h1>Thank you for the thoughtful brief.</h1>
        <p>Your request has reached Numerimondes. Yassine, our founder, and the account manager will review your brief and follow up personally using the contact details you provided. Any scope, timing and fee will be agreed with you before work begins.</p>
        <a href="{{ simple_site('studio', 'services') }}">Back to services <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
    </main>
</x-studio::page>
