<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Webkernel;

use Webkernel\Platform\Forms\View\FormsIconAlias;
use Webkernel\Platform\FontProviders\LocalFontProvider;
use Webkernel\Platform\Panel;
use Webkernel\Platform\Support\Facades\PlatformView;
use Webkernel\Platform\View\PanelsIconAlias;
use Webkernel\Platform\View\PanelsRenderHook;
use Webkernel\Route\Route;
use Webkernel\View\View;

/** Adds Webkernel-owned styles to native Platform panel render hooks. */
final class PlatformRenderHooks
{
    private const string VIEW_PREFIX = 'webkernel-platform::webkernel-special-css.panels.';

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Fonts and icon aliases are panel configuration. Visual CSS is registered
        // on PlatformView itself, as in Platform's documented render-hook API. A
        // Panel::configureUsing callback can run after the panel has already
        // registered its scoped hooks in Webkernel's route bootstrap lifecycle.
        Panel::configureUsing(static function (Panel $panel): void {
            $panel
                ->font('DM Sans', provider: LocalFontProvider::class)
                ->monoFont('Lilex', provider: LocalFontProvider::class)
                ->icons([
                    PanelsIconAlias::GLOBAL_SEARCH_FIELD => 'search',
                    PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => 'bell',
                    FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_CLONE => 'clipboard',
                    FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE => 'trash-2',
                    PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'panel-right-open',
                    PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'panel-left-open',
                ]);
        });

        PlatformView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            static fn (): string => self::render('layout._typography'),
        );
        PlatformView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            static fn (): string => self::isAuthPage()
                ? self::render('auth.css', ['bgLight' => '', 'bgDark' => ''])
                : '',
        );
        PlatformView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            static fn (): string => self::render('layout.css'),
        );
        PlatformView::registerRenderHook(
            PanelsRenderHook::TOPBAR_BEFORE,
            static fn (): string => self::hasTopbar() ? self::render('logo.logo-before') : '',
        );
        PlatformView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            static fn (): string => self::hasTopbar() ? '' : self::render('logo.logo-before'),
        );
    }

    /** @param array<string, mixed> $data */
    private static function render(string $view, array $data = []): string
    {
        return View::make(self::VIEW_PREFIX . $view, $data)->render();
    }

    private static function isAuthPage(): bool
    {
        $path = Route::currentRequest()->path();

        return preg_match('~(?:^|/)(?:login|register|password-reset)(?:/|$)~', $path) === 1;
    }

    private static function hasTopbar(): bool
    {
        try {
            return (bool) platform()->hasTopbar();
        } catch (\Throwable) {
            return true;
        }
    }
}
