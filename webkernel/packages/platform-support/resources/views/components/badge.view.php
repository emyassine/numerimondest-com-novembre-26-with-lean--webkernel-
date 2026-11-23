@props([
    'color' => 'primary',
    'deleteButton' => null,
    'disabled' => false,
    'form' => null,
    'formId' => null,
    'href' => null,
    'icon' => null,
    'iconAlias' => null,
    'iconPosition' => null,
    'iconSize' => null,
    'keyBindings' => null,
    'loadingIndicator' => true,
    'size' => null,
    'spaMode' => null,
    'tag' => 'span',
    'target' => null,
    'tooltip' => null,
    'type' => 'button',
])

@php
    use Webkernel\Platform\Support\Enums\IconPosition;
    use Webkernel\Platform\Support\Enums\IconSize;
    use Webkernel\Platform\Support\Enums\Size;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
    use Webkernel\Platform\Support\View\Components\BadgeComponent;
    use Webkernel\View\Htmlable;
    use Webkernel\View\AttributeBag;

    $attributes = new ComponentAttributeBag($attributes->getAttributes());

    $iconPosition ??= IconPosition::Before;
    $size ??= Size::Medium;

    if (! $iconPosition instanceof IconPosition) {
        $iconPosition = filled($iconPosition) ? (IconPosition::tryFrom($iconPosition) ?? $iconPosition) : null;
    }

    if (! $size instanceof Size) {
        $size = filled($size) ? (Size::tryFrom($size) ?? $size) : null;
    }

    if (filled($iconSize) && (! $iconSize instanceof IconSize)) {
        $iconSize = IconSize::tryFrom($iconSize) ?? $iconSize;
    }

    $isDeletable = count($deleteButton?->attributes->getAttributes() ?? []) > 0;

    $wireTarget = $loadingIndicator ? $attributes->whereStartsWith(['wire:target', 'wire:click'])->filter(fn ($value): bool => filled($value))->first() : null;

    $hasLoadingIndicator = filled($wireTarget) || ($type === 'submit' && filled($form));

    if ($hasLoadingIndicator) {
        $loadingIndicatorTarget = html_entity_decode($wireTarget ?: $form, ENT_QUOTES);
    }

    $hasTooltip = filled($tooltip);

    $loadingDelay = ($icon || $hasLoadingIndicator || $isDeletable)
        ? config('platform.liveview_loading_delay', 'default')
        : null;
@endphp

<{{ $tag }}
    @if (($tag === 'a') && (! ($disabled && $hasTooltip)))
        {{ \Webkernel\Platform\Support\generate_href_html($href, $target === '_blank', $spaMode) }}
    @endif
    @if ($keyBindings)
        x-bind:id="$id('key-bindings')"
        x-mousetrap.global.{{ collect($keyBindings)->map(fn (string $keyBinding): string => str_replace('+', '-', $keyBinding))->implode('.') }}="document.getElementById($el.id)?.click()"
    @endif
    @if ($hasTooltip)
        x-tooltip='{
            content: {!! htmlspecialchars(\Webkernel\View\View::js($tooltip), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) !!},
            theme: $store.theme,
            allowHTML: @js($tooltip instanceof Htmlable),
        }'
    @endif
    {{
        $attributes
            ->merge([
                'aria-disabled' => $disabled ? 'true' : null,
                'disabled' => $disabled && blank($tooltip),
                'form' => $tag === 'button' ? $formId : null,
                'type' => $tag === 'button' ? $type : null,
                'wire:loading.attr' => $tag === 'button' ? 'aria-disabled' : null,
                'wire:loading.class' => $tag === 'button' ? $attributes->prepends('fi-disabled') : null,
                'wire:target' => ($hasLoadingIndicator && $loadingIndicatorTarget) ? $loadingIndicatorTarget : null,
            ], escape: false)
            ->when(
                $disabled && $hasTooltip,
                fn (ComponentAttributeBag $attributes) => $attributes->filter(
                    fn (mixed $value, string $key): bool => ! str($key)->startsWith(['href', 'x-on:', 'wire:click']),
                ),
            )
            ->class([
                'fi-badge',
                'fi-disabled' => $disabled,
                ($size instanceof Size) ? "fi-size-{$size->value}" : (is_string($size) ? $size : ''),
            ])
            ->color(BadgeComponent::class, $color)
            ->style([
                'flex' => '0 0 auto',
                'width' => 'max-content',
                'max-width' => '100%',
                'flex-wrap' => 'nowrap',
                'white-space' => 'nowrap',
            ])
    }}
>
    @if ($iconPosition === IconPosition::Before)
        @if ($icon || $iconAlias)
            {{
                \Webkernel\Platform\Support\generate_icon_html($icon, $iconAlias, (new Webkernel\Platform\Support\View\ComponentAttributeBag([
                    'wire:loading.remove.delay.' . $loadingDelay => $hasLoadingIndicator,
                    'wire:target' => $hasLoadingIndicator ? $loadingIndicatorTarget : false,
                ])), size: $iconSize ?? IconSize::Small)
            }}
        @endif

        @if ($hasLoadingIndicator)
            {{
                \Webkernel\Platform\Support\generate_loading_indicator_html((new Webkernel\Platform\Support\View\ComponentAttributeBag([
                    'wire:loading.delay.' . $loadingDelay => '',
                    'wire:target' => $loadingIndicatorTarget,
                ])), size: $iconSize ?? IconSize::Small)
            }}
        @endif
    @endif

    <span class="fi-badge-label-ctn" style="display:inline-flex;min-width:0;align-items:center;flex-wrap:nowrap;white-space:nowrap">
        <span class="fi-badge-label" style="display:inline-flex;min-width:0;max-width:100%;align-items:center;flex-wrap:nowrap;column-gap:.25rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $slot }}
        </span>
    </span>

    @if ($isDeletable)
        @php
            $deleteButtonWireTarget = $deleteButton->attributes->whereStartsWith(['wire:target', 'wire:click'])->filter(fn ($value): bool => filled($value))->first();

            $deleteButtonHasLoadingIndicator = filled($deleteButtonWireTarget);

            if ($deleteButtonHasLoadingIndicator) {
                $deleteButtonLoadingIndicatorTarget = html_entity_decode($deleteButtonWireTarget, ENT_QUOTES);
            }
        @endphp

        <button
            type="button"
            {{
                $deleteButton->attributes
                    ->except(['label'])
                    ->class([
                        'fi-badge-delete-btn',
                    ])
            }}
        >
            <span
                {{
                    (new Webkernel\Platform\Support\View\ComponentAttributeBag([
                        'aria-hidden' => 'true',
                        'wire:loading.remove.delay.' . $loadingDelay => $deleteButtonHasLoadingIndicator,
                        'wire:target' => $deleteButtonHasLoadingIndicator ? $deleteButtonLoadingIndicatorTarget : false,
                    ]))->class([
                        'fi-badge-delete-btn-icon',
                    ])
                }}
            ></span>

            @if ($deleteButtonHasLoadingIndicator)
                {{
                    \Webkernel\Platform\Support\generate_loading_indicator_html((new Webkernel\Platform\Support\View\ComponentAttributeBag([
                        'wire:loading.delay.' . $loadingDelay => '',
                        'wire:target' => $deleteButtonLoadingIndicatorTarget,
                    ])), size: IconSize::ExtraSmall)
                }}
            @endif

            <span class="fi-sr-only">
                {{ filled($label = $deleteButton->attributes->get('label')) ? $label : __('webkernel::components/badge.actions.delete.label') }}
            </span>
        </button>
    @elseif ($iconPosition === IconPosition::After)
        @if ($icon || $iconAlias)
            {{
                \Webkernel\Platform\Support\generate_icon_html($icon, $iconAlias, (new Webkernel\Platform\Support\View\ComponentAttributeBag([
                    'wire:loading.remove.delay.' . $loadingDelay => $hasLoadingIndicator,
                    'wire:target' => $hasLoadingIndicator ? $loadingIndicatorTarget : false,
                ])), size: $iconSize ?? IconSize::Small)
            }}
        @endif

        @if ($hasLoadingIndicator)
            {{
                \Webkernel\Platform\Support\generate_loading_indicator_html((new Webkernel\Platform\Support\View\ComponentAttributeBag([
                    'wire:loading.delay.' . $loadingDelay => '',
                    'wire:target' => $loadingIndicatorTarget,
                ])), size: $iconSize ?? IconSize::Small)
            }}
        @endif
    @endif
</{{ $tag }}>
