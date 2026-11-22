@php
    use Webkernel\Platform\Support\Enums\IconPosition;
    use Webkernel\Platform\Support\Facades\PlatformAsset;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
    use Webkernel\Platform\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;
    use Webkernel\Platform\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\StatsOverviewWidgetStatChartComponent;

    $chartColor = $getChartColor() ?? 'gray';
    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Webkernel\Platform\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat',
            ])
    }}
>
    <div class="fi-wi-stats-overview-stat-content">
        <div class="fi-wi-stats-overview-stat-label-ctn">
            {{ \Webkernel\Platform\Support\generate_icon_html($getIcon()) }}

            <span class="fi-wi-stats-overview-stat-label">
                {{ $getLabel() }}
            </span>
        </div>

        @if (filled($value = $getValue()))
            <div class="fi-wi-stats-overview-stat-value">
                {{ $value }}
            </div>
        @else
            <div class="fi-wi-stats-overview-stat-placeholder">
                {{ $getPlaceholder() }}
            </div>
        @endif

        @if ($description = $getDescription())
            <div
                {{ (new PlatformComponentAttributeBag)->color(DescriptionComponent::class, $descriptionColor)->class(['fi-wi-stats-overview-stat-description']) }}
            >
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                    {{ \Webkernel\Platform\Support\generate_icon_html($descriptionIcon, attributes: (new PlatformComponentAttributeBag)) }}
                @endif

                <span>
                    {{ $description }}
                </span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                    {{ \Webkernel\Platform\Support\generate_icon_html($descriptionIcon, attributes: (new PlatformComponentAttributeBag)) }}
                @endif
            </div>
        @endif
    </div>

    @if ($chart = $getChart())
        {{-- An empty function to initialize the Alpine component with until it's loaded with `x-load`. --}}
        <div x-data="{ statsOverviewStatChart() {} }">
            <div
                x-load
                x-load-src="{{ PlatformAsset::getAlpineComponentSrc('stats-overview/stat/chart', 'webkernel/platform-widgets') }}"
                wire:ignore
                x-data="statsOverviewStatChart({
                            key: @js($getKey(false)),
                            labels: @js(array_keys($chart)),
                            values: @js(array_values($chart)),
                        })"
                {{ (new PlatformComponentAttributeBag)->color(StatsOverviewWidgetStatChartComponent::class, $chartColor)->class(['fi-wi-stats-overview-stat-chart']) }}
            >
                {{-- The label and value are already exposed as text, so the trend sparkline is decorative. --}}
                <canvas x-ref="canvas" aria-hidden="true"></canvas>

                {{--
                    These empty elements carry the colors the chart should be painted in, so that a theme
                    can set them with an ordinary `color` declaration.
                --}}
                <span
                    aria-hidden="true"
                    x-ref="backgroundColorElement"
                    class="fi-wi-stats-overview-stat-chart-bg-color"
                ></span>

                <span
                    aria-hidden="true"
                    x-ref="borderColorElement"
                    class="fi-wi-stats-overview-stat-chart-border-color"
                ></span>
            </div>
        </div>
    @endif
</{!! $tag !!}>
