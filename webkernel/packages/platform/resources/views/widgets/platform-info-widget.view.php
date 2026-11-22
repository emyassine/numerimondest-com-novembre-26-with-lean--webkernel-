@php
    use Composer\InstalledVersions;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
@endphp

<x-webkernel-widgets::widget class="webkernel-platform-info-widget">
    <x-webkernel-platform::section>
        <div class="webkernel-platform-info-widget-main">
            <a href="https://www.webkernelphp.com" class="webkernel-platform-info-widget-brand">Webkernel Platform</a>

            <p class="webkernel-platform-info-widget-version">
                {{ InstalledVersions::getPrettyVersion('webkernel/platform') }}
            </p>
        </div>

        <div class="webkernel-platform-info-widget-links">
            <x-webkernel-platform::link
                color="gray"
                href="https://www.webkernelphp.com"
                :icon="Heroicon::BookOpen"
                :icon-alias="PanelsIconAlias::WIDGETS_FILAMENT_INFO_OPEN_DOCUMENTATION_BUTTON"
                rel="noopener noreferrer"
                target="_blank"
            >
                {{ __('webkernel-platform::widgets/platform-info-widget.actions.open_documentation.label') }}
            </x-webkernel-platform::link>
        </div>
    </x-webkernel-platform::section>
</x-webkernel-widgets::widget>
