@props(['canonical' => '', 'locale' => 'en'])
@php
    $studioConfig = config('nm-studio', []);
@endphp

<x-studio::website title="Contact — Numerimondes" description="Contact Numerimondes about a project, a partnership or a technical discussion." :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []">
<main class="nm-page nm-contact-page">
    <header class="nm-page-heading" style="min-height: 5vh !important; margin-top:10rem;"><h1 class="silky-ri">Contact</h1></header>
    <section class="nm-contact-form" aria-label="Contact Numerimondes" style="width:100%;margin:0 auto 100px;display:grid;place-items:center">
        <iframe
            title="Contact Numerimondes"
            src="{{ $studioConfig['contact']['form']['embed'] }}"
            frameborder="0"
            allowfullscreen
            style="display:block;width:100%;height:max(100vh,850px);border:0;border-radius:var(--radius-md)"
        ></iframe>
        <p style="margin:24px 0 0;text-align:center">
            If the form does not load, <a href="{{ $studioConfig['contact']['form']['direct'] }}" target="_blank" rel="noreferrer">open it directly</a>.
        </p>
    </section>
</main>
</x-studio::website>
