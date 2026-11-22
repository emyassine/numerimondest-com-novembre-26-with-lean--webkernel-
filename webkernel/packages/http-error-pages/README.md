# HTTP Error Pages

`webkernel/http-error-pages` supplies localized server-rendered HTML for HTTP failures. Codebase renders the selected page after route dispatch, including 404/405/authorization responses, failed CSRF requests, and unhandled exceptions. The original HTTP status is preserved. Exception messages are logged server-side and are hidden in the response by default.

The package provider registers `http-error-pages::errors.show` and the package configuration. Override any default in the application `config/http-error-pages.php` file, for example:

```php
return [
    'view' => 'http-error-pages::errors.show',
    'show_exception_details' => false,
    'statuses' => [
        403 => [
            'title' => ['en' => 'Private area', 'fr' => 'Espace privé'],
            'description' => ['en' => 'Ask an administrator for access.', 'fr' => 'Demandez l’accès à un administrateur.'],
            'accent' => 'warning',
        ],
    ],
];
```

The same values are available through `config('http-error-pages.statuses.403.title')`. Each status may define `title`, `description`, `icon`, and `accent`; a `default` entry fills values that a status does not override. Text can be a plain string or a locale-to-string map. Unsupported accents are replaced with a safe neutral accent. Applications may provide their own view through the `view` setting.

The built-in page resolves locale from supported request preferences and falls back to configured localization. It uses the existing `Webkernel\I18n\Support\ErrorPageLocale` and `LocaleDirection` services. This first package focuses on regular HTML responses; API content negotiation and custom error layouts per panel remain future work.
