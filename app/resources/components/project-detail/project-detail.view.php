@props([
    'project' => [],
])

@php
    $coverPath = base_path('app/resources/views/pages/'.$project['cover']);
    $coverDimensions = is_file($coverPath) ? @getimagesize($coverPath) : false;
    $coverDimensions = $coverDimensions ?: [1200, 800];
    $logoPath = base_path('app/resources/views/pages/'.($project['logo'] ?? ''));
    $logoDimensions = ! empty($project['logo']) && is_file($logoPath) ? @getimagesize($logoPath) : false;
    if (! $logoDimensions && ! empty($project['logo']) && is_file($logoPath) && strtolower(pathinfo($logoPath, PATHINFO_EXTENSION)) === 'svg') {
        $logoContents = @file_get_contents($logoPath);
        if (is_string($logoContents) && preg_match('/viewBox=["\']\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)/', $logoContents, $viewBox) === 1) {
            $logoDimensions = [(int) round((float) $viewBox[1] * 100), (int) round((float) $viewBox[2] * 100)];
        }
    }
    $logoDimensions = $logoDimensions ?: [260, 130];
@endphp

<x-studio::page
    class="bleed-page"
    :title="$project['name'].' — Numerimondes'"
    :description="$project['introduction']"
>
    <main class="nm-page nm-project-detail project--{{ $project['slug'] }}">
        <header class="nm-project-detail-intro">
            <div class="nm-project-detail-identity">
                @if (! empty($project['logo']))
                    <img
                        class="nm-project-detail-logo"
                        src="{{ simple_site('studio', $project['logo']) }}"
                        alt="{{ $project['logo_alt'] ?? $project['name'] }}"
                        width="{{ $logoDimensions[0] }}"
                        height="{{ $logoDimensions[1] }}"
                    >
                @else
                    <p class="nm-project-detail-wordmark">{{ $project['name'] }}</p>
                @endif
                <p class="nm-project-detail-meta">
                    {{ $project['category'] }} <span aria-hidden="true">·</span> {{ $project['status'] }}
                </p>
            </div>

            <div class="nm-project-detail-summary">
                <h1>{{ $project['headline'] }}</h1>
                <p>{{ $project['introduction'] }}</p>
            </div>
        </header>

        <figure class="nm-project-detail-cover">
            <img
                src="{{ simple_site('studio', $project['cover']) }}"
                alt="{{ $project['cover_alt'] }}"
                width="{{ $coverDimensions[0] }}"
                height="{{ $coverDimensions[1] }}"
                fetchpriority="high"
            >
            <figcaption>{{ $project['visual_note'] }}</figcaption>
        </figure>

        <div class="nm-project-detail-sections">
            @foreach ($project['sections'] as $section)
                <section class="nm-project-detail-section">
                    <h2>{{ $section['title'] }}</h2>

                    <div class="nm-project-detail-copy">
                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach

                        @if (! empty($section['items']))
                            <ul>
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($section['note']))
                            <p class="nm-project-detail-note">{{ $section['note'] }}</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        <section class="nm-project-detail-cta">
            <h2>Explore this with Numerimondes</h2>
            <form class="nm-service-inquiry-launch" action="{{ simple_site('studio', 'service-inquiry/select') }}" method="post">
                @csrf
                @if (is_string($project['call_to_action']['service_slug'] ?? null))
                    <input type="hidden" name="services[]" value="{{ $project['call_to_action']['service_slug'] }}">
                @endif
                <input type="hidden" name="projects[]" value="{{ $project['slug'] }}">
                <button type="submit" class="nm-project-detail-cta-button">{{ $project['call_to_action']['label'] }}</button>
            </form>
        </section>

        @php
            $allProjects = \App\Studio\ProjectCatalog::all();
            $relatedProjects = array_filter(
                array_map(
                    static fn (string $slug): ?array => $allProjects[$slug] ?? null,
                    $project['related'] ?? [],
                ),
            );
        @endphp

        @if ($relatedProjects !== [])
            <section class="nm-next-project" aria-labelledby="nm-related-projects-title">
                <h2 id="nm-related-projects-title">Other projects</h2>

                <x-studio::showcase-list
                    :cards="$relatedProjects"
                    card-type="project"
                    variant="grid"
                    modifier="nm-projects--related"
                    label="Other projects"
                    heading-level="3"
                />
            </section>
        @endif
    </main>
</x-studio::page>
