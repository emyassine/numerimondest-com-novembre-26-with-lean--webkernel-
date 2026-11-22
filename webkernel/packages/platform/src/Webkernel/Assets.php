<?php declare(strict_types=1);
//> This file is part of Platform, integrated with Webkernel.
//> Platform is licensed under the MIT license.

namespace Webkernel\Platform\Webkernel;

use Webkernel\Platform\Support\Assets\AlpineComponent;
use Webkernel\Platform\Support\Assets\AssetManager;
use Webkernel\Platform\Support\Assets\Font;
use Webkernel\Platform\Support\Assets\Js;
use Webkernel\Platform\Support\Assets\Theme;
use Composer\InstalledVersions;
use LogicException;

/** Registers Platform's compiled assets for Webkernel requests. */
final class Assets
{
    public static function register(AssetManager $manager): void
    {
        $packageRoot = static function (string $package): string {
            $path = InstalledVersions::getInstallPath($package);
            if (! is_string($path)) {
                throw new LogicException("Required Platform asset package [{$package}] is not installed.");
            }

            return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        };

        $platform = $packageRoot('webkernel/platform');
        $support = $packageRoot('webkernel/platform-support');
        $actions = $packageRoot('webkernel/platform-actions');
        $forms = $packageRoot('webkernel/platform-forms');
        $schemas = $packageRoot('webkernel/platform-schemas');
        $tables = $packageRoot('webkernel/platform-tables');
        $widgets = $packageRoot('webkernel/platform-widgets');
        $notifications = $packageRoot('webkernel/platform-notifications');

        $manager->register([
            Font::make('inter', $platform . 'dist/fonts/inter'),
            Js::make('app', $platform . 'dist/index.js')->core(),
            Js::make('echo', $platform . 'dist/echo.js')->core(),
            Theme::make('app', $platform . 'dist/theme.css'),
        ], 'webkernel/platform');

        $manager->register([
            Js::make('support', $support . 'dist/index.js'),
        ], 'webkernel/platform-support');

        $manager->register([
            Js::make('actions', $actions . 'dist/index.js'),
        ], 'webkernel/platform-actions');

        $manager->register([
            AlpineComponent::make('builder', $forms . 'dist/components/builder.js'),
            AlpineComponent::make('checkbox-list', $forms . 'dist/components/checkbox-list.js'),
            AlpineComponent::make('code-editor', $forms . 'dist/components/code-editor.js'),
            AlpineComponent::make('color-picker', $forms . 'dist/components/color-picker.js'),
            AlpineComponent::make('date-time-picker', $forms . 'dist/components/date-time-picker.js'),
            AlpineComponent::make('file-upload', $forms . 'dist/components/file-upload.js'),
            AlpineComponent::make('key-value', $forms . 'dist/components/key-value.js'),
            AlpineComponent::make('markdown-editor', $forms . 'dist/components/markdown-editor.js'),
            AlpineComponent::make('repeater', $forms . 'dist/components/repeater.js'),
            AlpineComponent::make('rich-editor', $forms . 'dist/components/rich-editor.js'),
            AlpineComponent::make('select', $forms . 'dist/components/select.js'),
            AlpineComponent::make('slider', $forms . 'dist/components/slider.js'),
            AlpineComponent::make('tags-input', $forms . 'dist/components/tags-input.js'),
            AlpineComponent::make('textarea', $forms . 'dist/components/textarea.js'),
            AlpineComponent::make('text-input/password-reveal', $forms . 'dist/components/text-input/password-reveal.js'),
            AlpineComponent::make('toggle-buttons', $forms . 'dist/components/toggle-buttons.js'),
        ], 'webkernel/platform-forms');

        $manager->register([
            AlpineComponent::make('actions', $schemas . 'dist/components/actions.js'),
            AlpineComponent::make('tabs', $schemas . 'dist/components/tabs.js'),
            AlpineComponent::make('wizard', $schemas . 'dist/components/wizard.js'),
            Js::make('schemas', $schemas . 'dist/index.js'),
        ], 'webkernel/platform-schemas');

        $manager->register([
            AlpineComponent::make('columns/checkbox', $tables . 'dist/components/columns/checkbox.js'),
            AlpineComponent::make('columns/select', $tables . 'dist/components/columns/select.js'),
            AlpineComponent::make('columns/text-input', $tables . 'dist/components/columns/text-input.js'),
            AlpineComponent::make('columns/toggle', $tables . 'dist/components/columns/toggle.js'),
            Js::make('tables', $tables . 'dist/index.js'),
        ], 'webkernel/platform-tables');

        $manager->register([
            AlpineComponent::make('chart', $widgets . 'dist/components/chart.js'),
            AlpineComponent::make('stats-overview/stat/chart', $widgets . 'dist/components/stats-overview/stat/chart.js'),
        ], 'webkernel/platform-widgets');

        $manager->register([
            Js::make('notifications', $notifications . 'dist/index.js'),
        ], 'webkernel/platform-notifications');
    }
}
