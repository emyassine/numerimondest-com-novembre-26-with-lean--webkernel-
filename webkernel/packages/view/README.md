# Webkernel Views

Views are PHP templates with the `.view.php` suffix. A package declares its view directories through `ServiceProvider::VIEWS`; Codebase compiles the explicit namespace map at Composer autoload time. Rendering resolves one view path from that map and does not search module trees.

```php
use Symfony\Component\HttpFoundation\Response;
use Webkernel\View\View;

return new Response(View::make('jobs-platform::pages.index', ['title' => lang('messages.jobs')])->render());
```

Template data is extracted without overwriting the renderer's local variables. `View::share()` provides application-wide values. The compiler supports `@php` ... `@endphp`, conditionals, loops, layouts, sections, escaped `{{ }}` output, trusted `{!! !!}` output, and components. Compiled templates are cached under `runtime/cache/views/`.

Component props become template variables; remaining attributes are exposed as `Webkernel\View\AttributeBag`, which supports class merging and safe HTML serialization. `@markdown` ... `@endmarkdown` compiles Markdown while passing HTML and view directives through the view compiler; escaped view echoes remain escaped. Use HTML for custom attributes and arbitrary markup. Inside a block, a fenced `view parse+` section passes its contents directly to the view compiler. `@markdown($source)` renders escaped Markdown, including images with optional titles and relative links. `Markdown::render($source, allow_html: true)` is for trusted author-written Markdown that needs raw HTML. Rendered output is cached by source and HTML mode. Markdown headings receive the `markdown-heading` class. Package components are registered in the Composer compiled component manifest.

Deferred render hooks use `<x-renderhook id="sidebar">...</x-renderhook>` inside a component call. The receiving component places `<x-renderhook id="sidebar" />` at its render outlet. Definitions capture their declaration scope; markup, PHP, directives, and nested components execute only when an enabled outlet is reached, once per component render. `$renderhooks->has('sidebar')` checks availability without evaluating content. `$renderhooks->select($include, $exclude)` filters inherited definitions using comma-separated strings or arrays; include and exclude are mutually exclusive. Hook IDs are literal and definitions are scoped to their enclosing component. A definition may declare `variant="documentation"`; the receiving component selects variants with the third `select()` argument, for example `['sidebar' => 'documentation']`. The layout retains one outlet per hook ID. Unselected variants execute no content code.

Use `{{ $value }}` or `e($value)` for untrusted text in HTML. Templates keep page-level markup thin; forms, layout, actions, and notifications belong to schemas.

Blade-style directives such as `@js`, `@class`, `@style`, `@capture`, and `@lang` compile through Webkernel. A package can register a directive at runtime with `View::directive('badge', $handler)`, or declare it in its `ServiceProvider::VIEW_DIRECTIVES` map so Webkernel registers it when the HTTP runtime boots:

```php
public const array VIEW_DIRECTIVES = [
    'badge' => [BadgeDirectives::class, 'badge'],
];

final class BadgeDirectives
{
    public static function badge(string $expression): string
    {
        return '<?php echo Badge::render' . $expression . '; ?>';
    }
}
```

Handlers receive the original parenthesized expression and may also accept the current compile `State` for source-aware output or paired block directives. They return PHP source for the compiled template. Names may replace built-in directives; duplicate declarations between providers are rejected. Runtime conditions use `View::if('feature', $callback)` and the matching `@feature(...)` / `@endfeature` tags.

Packages can also extend the view APIs with `View::macro('name', $callback)` or `ViewResult::macro('name', $callback)`. `View` macros add static view operations; result macros add chainable methods to the object returned by `View::make()`.

Frontend ownership follows the application rules: Tailwind and Vite for styles and assets, HTMX for server-driven HTML, Alpine for local declarative state, and vanilla JavaScript for browser APIs. Keep module assets and dependency scopes local. PWA, offline synchronization, and native packaging remain opt-in features.

Translation catalogs use `resources/lang/{locale}/translations.php` and return nested arrays. Register their roots with `ServiceProvider::LANG_PATH` and use `lang('messages.key')` in a view. Catalog roots are explicit; runtime does not scan for files.
