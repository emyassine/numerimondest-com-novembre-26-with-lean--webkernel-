@php
    $projects = \App\Studio\ProjectCatalog::initiatives();
@endphp

<x-studio::page
    title="Projects — Numerimondes"
    description="Explore projects and initiatives developed by Numerimondes."
    :page-width="null"
>
    <style>
        .nm-projects-page > .nm-about-section { padding-inline: 0; }
    </style>
    <main class="nm-page nm-projects-page">

        <section class="nm-about-section" style="margin-top:5rem;">
            <div>
                <h2>Ideas in development</h2>
            </div>
            <div>
                <p>Explore initiatives at different stages of development. Each page identifies its current status and distinguishes concepts from available services.</p>
            </div>
        </section>è
        <style>
	        @media (max-width: 1100px) {
	          .nm-projects-page .nm-projects.nm-projects--grid.nm-projects--listing {
	            grid-template-columns: repeat(2, minmax(0, 1fr));
	          }
	        }

	        @media (max-width: 700px) {
	          .nm-projects-page .nm-projects.nm-projects--grid.nm-projects--listing {
	            grid-template-columns: minmax(0, 1fr);
	            gap: 8px;
	          }

	          .nm-projects-page .nm-projects.nm-projects--grid.nm-projects--listing .nm-project {
	            height: 72vh;
	            height: 72svh;
	            min-height: 360px;
	          }
	        }
        </style>

        <x-studio::showcase-list
            :cards="$projects"
            variant="grid"
            card-type="project"
            modifier="nm-projects--listing"
            label="Numerimondes projects"
        />
    </main>
</x-studio::page>
