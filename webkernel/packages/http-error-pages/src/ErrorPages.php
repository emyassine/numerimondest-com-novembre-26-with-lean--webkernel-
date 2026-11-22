<?php declare(strict_types=1);

namespace Webkernel\HttpErrorPages;

use Throwable;
use Webkernel\Config\Config;
use Webkernel\I18n\Support\ErrorPageLocale;
use Webkernel\I18n\Support\LocaleDirection;
use Webkernel\Route\Route;
use Webkernel\View\View;

final class ErrorPages
{
    public static function render(int $status, ?Throwable $exception = null): string
    {
        $request = Route::currentRequest();
        $locale = ErrorPageLocale::apply($request);
        $statuses = Config::get('http-error-pages.statuses', []);
        $entry = is_array($statuses) ? ($statuses[$status] ?? $statuses['default'] ?? []) : [];
        if (! is_array($entry)) { $entry = []; }
        $fallback = is_array($statuses) ? ($statuses['default'] ?? []) : [];
        $fallback = is_array($fallback) ? $fallback : [];
        $entry = array_replace($fallback, $entry);
        $view = Config::get('http-error-pages.view', 'http_error_pages::errors.show');
        try {
            $valid_view = is_string($view) && $view !== '' && View::exists($view);
        } catch (Throwable) {
            $valid_view = false;
        }
        if (! $valid_view) { $view = 'http_error_pages::errors.show'; }
        $details = Config::get('http-error-pages.show_exception_details', false) === true && $exception !== null;

        return View::make($view, [
            'status' => $status,
            'locale' => $locale,
            'direction' => LocaleDirection::for($locale),
            'title' => self::localized($entry['title'] ?? null, $locale, 'Something went wrong'),
            'description' => self::localized($entry['description'] ?? null, $locale, 'Please try again later.'),
            'accent' => self::accent($entry['accent'] ?? 'gray'),
            'icon' => is_string($entry['icon'] ?? null) ? $entry['icon'] : 'exclamation',
            'exception_message' => $details ? $exception->getMessage() : null,
        ])->render();
    }

    private static function localized(mixed $value, string $locale, string $fallback): string
    {
        if (is_string($value)) { return $value; }
        if (! is_array($value)) { return $fallback; }
        $text = $value[$locale] ?? $value['en'] ?? reset($value) ?? $fallback;
        return is_string($text) ? $text : $fallback;
    }

    private static function accent(mixed $value): string
    {
        return in_array($value, ['danger', 'gray', 'info', 'success', 'warning'], true) ? $value : 'gray';
    }
}
