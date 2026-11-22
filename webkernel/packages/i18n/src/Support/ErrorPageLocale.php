<?php declare(strict_types=1);

namespace Webkernel\I18n\Support;

use Webkernel\Config\Config;
use Webkernel\I18n\I18nContext;
use Webkernel\Route\Request;

/** Restores a supported interface locale when an error bypasses locale middleware. */
final class ErrorPageLocale
{
    public static function apply(Request $request): string
    {
        $supported = Config::get('localization.supported_locales', ['en']);
        $supported = is_array($supported) ? array_values(array_filter($supported, 'is_string')) : ['en'];
        $candidate = $request->cookies()[I18nContext::COOKIE] ?? '';
        if (! is_string($candidate) || ! in_array(I18nContext::normalize($candidate), $supported, true)) {
            $candidate = I18nContext::get_locale();
        }
        if (! in_array($candidate, $supported, true)) {
            $accept = $request->header('accept-language', '');
            $candidate = '';
            if (is_string($accept)) {
                foreach (explode(',', $accept) as $part) {
                    $locale = I18nContext::normalize(trim(explode(';', $part, 2)[0]));
                    if (in_array($locale, $supported, true)) { $candidate = $locale; break; }
                }
            }
        }
        if (! in_array($candidate, $supported, true)) {
            $candidate = Config::get('localization.locale', 'en');
        }
        if (! is_string($candidate) || ! in_array($candidate, $supported, true)) { $candidate = 'en'; }
        I18nContext::set_locale($candidate);
        return $candidate;
    }
}
