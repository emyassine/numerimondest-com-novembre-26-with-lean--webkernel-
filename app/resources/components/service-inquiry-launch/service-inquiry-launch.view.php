@props(['services' => [], 'projects' => [], 'class' => ''])

@php
    $selectedSlugs = array_values(array_unique(array_filter(array_map(
        static fn (mixed $service): mixed => is_array($service) ? ($service['slug'] ?? null) : $service,
        $services,
    ), static fn (mixed $slug): bool => is_string($slug) && $slug !== '')));
    $projectSlugs = array_values(array_unique(array_filter(array_map(
        static fn (mixed $project): mixed => is_array($project) ? ($project['slug'] ?? null) : $project,
        $projects,
    ), static fn (mixed $slug): bool => is_string($slug) && $slug !== '')));
@endphp

<form class="nm-service-inquiry-launch" action="{{ simple_site('studio', 'service-inquiry/select') }}" method="post">
    @csrf
    @foreach ($selectedSlugs as $slug)
        <input type="hidden" name="services[]" value="{{ $slug }}">
    @endforeach
    @foreach ($projectSlugs as $slug)
        <input type="hidden" name="projects[]" value="{{ $slug }}">
    @endforeach
    <button type="submit" class="{{ $class }}">{{ $slot }}</button>
</form>
