@props([
    'breadcrumbs' => [],
])

@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
    use Webkernel\Platform\Support\View\SupportIconAlias;

    use function Webkernel\Platform\Support\generate_icon_html;
@endphp

<nav
    {{
        $attributes
            ->merge(['aria-label' => __('webkernel::components/breadcrumbs.label')], escape: true)
            ->class(['fi-breadcrumbs'])
    }}
>
    <ol class="fi-breadcrumbs-list">
        @foreach ($breadcrumbs as $url => $label)
            <li class="fi-breadcrumbs-item">
                @if (! $loop->first)
                    {{
                        generate_icon_html(Heroicon::ChevronRight, alias: SupportIconAlias::BREADCRUMBS_SEPARATOR, attributes: (new PlatformComponentAttributeBag)->merge(['aria-hidden' => 'true'], escape: false)->class([
                            'fi-breadcrumbs-item-separator fi-ltr',
                        ]))
                    }}

                    {{
                        generate_icon_html(Heroicon::ChevronLeft, alias: SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL, attributes: (new PlatformComponentAttributeBag)->merge(['aria-hidden' => 'true'], escape: false)->class([
                            'fi-breadcrumbs-item-separator fi-rtl',
                        ]))
                    }}
                @endif

                @if (is_int($url))
                    <span
                        @if ($loop->last) aria-current="page" @endif
                        class="fi-breadcrumbs-item-label"
                    >
                        {{ $label }}
                    </span>
                @else
                    <a
                        {{ \Webkernel\Platform\Support\generate_href_html($url) }}
                        @if ($loop->last) aria-current="page" @endif
                        class="fi-breadcrumbs-item-label"
                    >
                        {{ $label }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
