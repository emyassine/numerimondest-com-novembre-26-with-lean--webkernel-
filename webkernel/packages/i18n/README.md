# Webkernel I18n

View-owned labels use inline locale maps:

```html
<h1>{{ __t(['en' => 'Documentation', 'fr' => 'Documentation']) }}</h1>
<p>{{ __t(['en' => 'Welcome, :name', 'fr' => 'Bienvenue, :name'], ['name' => $name]) }}</p>
```

`__t()` follows `I18nContext`, then the configured default locale, then the first supplied translation. Plain strings pass through. An optional third argument selects an explicit locale. The helper returns text; normal view echoes escape it.

The package preserves Webkernel's catalog contract: each declared language directory contains `{locale}/translations.php`, which returns the locale's nested translation tree.

```php
// resources/lang/en/translations.php
return [
    'welcome' => 'Welcome, :name',
];

lang('welcome', ['name' => $company_name]);
```

Packages declare catalog roots with `ServiceProvider::LANG_PATH`. Composer compiles those roots into `webkernel_lang.php`; `Catalog` merges catalogs in declaration order and caches the loaded locale for the process. A missing key falls back to `localization.locale`, then returns the key.

`I18nContext` resolves the active locale from explicit request state, the `webkernel_locale` cookie, and application configuration. `fast_i18n()` is available for inline maps. `translated_value()` and `fast_i18n_model()` support translated model-column envelopes. Locale labels and text direction use PHP's `intl` extension.
