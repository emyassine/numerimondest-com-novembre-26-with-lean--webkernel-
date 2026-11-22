<?php declare(strict_types=1);

return [
    /* Set false to allow pinch zoom on mobile. */
    'prevent_mobile_zoom' => true,
    /* Set false to let WebKernel/Filament and the browser follow system dark mode. */
    'force_light_mode' => true,

    /* Platform view components (tabs, badges, buttons, etc.) need the compiled Webkernel theme outside panel routes. */
    'webkernel_platform_theme' => true,

    /* Dependable UI accepts wildcard stylesheet sources. The website view expands them into public asset URLs. */
    'stylesheets' => [
        'app/resources/css/studio/base.css',
        'app/resources/components/*/*.css',
        'app/resources/css/studio/*.css',
    ],
    'themes' => [
        'sources' => [
            'app/resources/css/nm-studio.css',
        ],
        'css_output' => 'app/resources/css/dependable-ui.themes.css',
        'javascript_output' => 'app/resources/js/dependable-ui.themes.js',
    ],
];
