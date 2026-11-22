@props([
    'canonical' => '',
    'locale' => 'en',
])

@php
    $projects = \App\Studio\ProjectCatalog::initiatives();
@endphp

<x-studio::website
    title="Projects — Numerimondes"
    description="Explore projects and initiatives developed by Numerimondes."
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
>
    <main class="nm-page nm-projects-page">
        <header class="nm-page-heading">
            <h1>Projects &amp; initiatives</h1>
        </header>

        <section class="nm-about-story">
            <h2>Ideas in development</h2>
            <p>Explore initiatives at different stages of development. Each page identifies its current status and distinguishes concepts from available services.</p>
        </section>

        <x-studio::showcase-list
            :cards="$projects"
            variant="grid"
            card-type="project"
            modifier="nm-projects--listing"
            label="Numerimondes projects"
        />
    </main>
</x-studio::website>
