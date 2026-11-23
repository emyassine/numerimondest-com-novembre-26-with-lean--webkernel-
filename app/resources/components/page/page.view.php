@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'canonical' => '',
    'locale' => 'en',
    'site_ui' => [],
])

<x-studio::website
    :title="$title"
    :description="$description"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui"
>
    <style>
        .studio-page-frame { padding-inline: 50px; }
        .studio-page-frame.bleed-page { padding-inline: 0; }
        .nm-page { width: 100%; margin: 0; padding: 0; }
        .nm-page [x-cloak] { display: none !important; }
        .nm-page h1,
        .nm-page h2,
        .nm-page h3 {
            font-family: var(--nm-title);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: .19em;
        }
        .nm-page-heading {
            margin: 0;
            min-height: 60vh;
            display: grid;
            place-items: center;
            text-align: center;
        }
        .nm-page-heading h1 {
            margin: 0;
            font: 400 16px/1.63 var(--nm-title);
            letter-spacing: .19em;
            text-transform: uppercase;
        }
        .nm-page-heading h1.silky-ri {
            font: 400 18px/1.63 Romie, Georgia, serif;
            font-style: italic;
            letter-spacing: normal;
            text-transform: none;
        }
        @media (max-width: 700px) {
            .studio-page-frame { padding-inline: 20px; }
            .studio-page-frame.bleed-page { padding-inline: 0; }
            .nm-page-heading { min-height: 55vh; }
        }
    </style>
    <div {{ $attributes->class(['studio-page-frame']) }}>{{ $slot }}</div>
</x-studio::website>
