# Webkernel Simple Site

Each site may define a `ui` array in `config/simple-site.php`. Simple Site passes it to discovered page views as `$site_ui`, allowing a site's shared view to opt into reusable Dependable UI behaviors without hardcoding those choices into the framework. For example, `['header' => ['contrast' => 'sections']]` enables section-aware header contrast. The layout can apply Dependable UI's `srs-auto-contrast` utility and mark regions with `data-srs-header-tone="light"`; see Dependable UI's base README for the browser runtime contract.

Files placed under a site's configured `pages` directory are public at the same relative URL. For example, `pages/cases/image.png` is served at `/cases/image.png` under that site's prefix. Common raster/vector images, PDFs, fonts, JSON, CSS, and JavaScript files are supported; template, metadata, and other files are not exposed. SVG responses are sandboxed.

File-based page discovery and SEO/discovery routes for Webkernel applications.

Configure each site under `simple-site.sites` in `config/simple-site.php` with a URL `prefix`, a `pages` directory, and a `view_namespace`. The directory must contain a root `index.view.php`. Each `.view.php` is a normal Webkernel page view and can invoke its website component with page-specific props and a content slot. Optional sibling `.meta.php` files return operational props such as `ogImage`, `robots`, and `updated_at`.

Use `simple_site($siteId, $subpath = '')` to build a site's path from its configured prefix. It returns a path, not an absolute URL, so combine it with `app_url()` when a full URL is needed. For example, `app_url(simple_site('marketing', 'legal/terms/'))` follows the current prefix in configuration.

Set `terms_page` on a site to have the package register the stable `/legal/terms/` redirect automatically. Configure `robots_file` and `tdmrep_file` at the package level to serve the corresponding root policy endpoints without application routes.

Directory and filename order prefixes like `01-services/02-executive-desk/index.view.php` are removed from the public URL while preserving their physical view names. The example maps to `/services/executive-desk/`. Duplicate normalized routes fail discovery.

Simple Site registers each configured site's root, catch-all page, `sitemap.xml`, and `llms.txt` routes. It also registers one root `robots.txt`, which lists configured AI crawler restrictions, preserves general crawler access, and links the configured sitemaps. These rules express crawler preferences and do not authenticate requests.

Canonical URLs are generated from `app.url`, the site prefix, and the discovered path. Site `locales` and `fallback_locale` control request locale selection; the package also registers a language preference endpoint at `/{site-prefix}/language`. A site may set `currency_config` to a configuration path containing `cookie`, `default`, and an `options` map; the package then registers `/{site-prefix}/currency` to persist an allowed currency preference. The package does not infer a visitor's country or convert prices. An `og_image` array supplies the default Open Graph/Twitter image set; a page can supply its own `ogImage` metadata array. Google Search Console's site verification value is available to the website component through `google.site_verification`.
