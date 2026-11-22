<?php

namespace Webkernel\Platform\Forms\Components;

use Webkernel\Platform\Schemas\Components\StateCasts\BooleanStateCast;
use Webkernel\Platform\Schemas\Components\StateCasts\Contracts\StateCast;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\HasExtraAlpineAttributes;
use Webkernel\Platform\Support\Enums\IconSize;
use Webkernel\Platform\Support\Enums\VerticalAlignment;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\Platform\Support\View\Components\ToggleComponent;
use Webkernel\Utilities\Arr;
use Webkernel\View\HtmlString;
use Webkernel\View\Js;

use function Webkernel\Platform\Support\generate_icon_html;
use function Webkernel\Platform\Support\get_component_color_classes;

class Toggle extends Field implements HasEmbeddedView
{
    use Concerns\CanBeAccepted;
    use Concerns\CanBeInline;
    use Concerns\CanFixIndistinctState;
    use Concerns\HasToggleColors;
    use Concerns\HasToggleIcons;
    use HasExtraAlpineAttributes;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-forms::components.toggle';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);

        $this->rule('boolean');
    }

    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();
        $offColor = $this->getOffColor() ?? 'gray';
        $offIcon = $this->getOffIcon();
        $onColor = $this->getOnColor() ?? 'primary';
        $onIcon = $this->getOnIcon();
        $isOn = (bool) $this->getState();
        $livewireKey = $this->getLivewireKey();

        $stateExpression = '$wire.' . $this->applyStateBindingModifiers("\$entangle('{$statePath}')");

        $toggleAttributes = (new PlatformComponentAttributeBag)
            ->merge([
                'aria-checked' => $isOn ? 'true' : 'false',
                'autofocus' => $this->isAutofocused(),
                'disabled' => $this->isDisabled(),
                'id' => $this->getId(),
                'wire:target' => $statePath,
            ], escape: false)
            ->merge($this->getExtraAttributes(), escape: false)
            ->merge($this->getExtraAlpineAttributes(), escape: false)
            ->merge($this->getAccessibilityAttributes(), escape: false)
            ->class(['fi-fo-toggle']);

        $onClasses = Js::from(Arr::toCssClasses([
            'fi-toggle-on',
            ...get_component_color_classes(ToggleComponent::class, $onColor),
        ]));

        $offClasses = Js::from(Arr::toCssClasses([
            'fi-toggle-off',
            ...get_component_color_classes(ToggleComponent::class, $offColor),
        ]));

        $offIconHtml = generate_icon_html($offIcon, size: IconSize::ExtraSmall)?->toHtml();
        $onIconHtml = generate_icon_html($onIcon, size: IconSize::ExtraSmall)?->toHtml();

        ob_start(); ?>

        <button
            x-data="{ state: <?= $stateExpression ?> }"
            x-bind:aria-checked="state ? 'true' : 'false'"
            x-on:click="state = ! state"
            x-bind:class="state ? <?= $onClasses ?> : <?= $offClasses ?>"
            <?php if ($isOn) { ?>
                x-cloak
            <?php } ?>
            role="switch"
            type="button"
            <?= $toggleAttributes->class(['fi-toggle'])->toHtml() ?>
        >
            <div>
                <div aria-hidden="true"><?= $offIconHtml ?></div>
                <div aria-hidden="true"><?= $onIconHtml ?></div>
            </div>
        </button>

        <?php if ($isOn) { ?>
            <div
                x-cloak="inline-flex"
                wire:ignore
                wire:key="<?= e($livewireKey) ?>.placeholder"
                <?= (new PlatformComponentAttributeBag)->class([
                    'fi-toggle fi-toggle-on fi-hidden',
                    ...get_component_color_classes(ToggleComponent::class, $onColor),
                ])->toHtml() ?>
            >
                <div>
                    <div aria-hidden="true"></div>
                    <div aria-hidden="true"><?= $onIconHtml ?></div>
                </div>
            </div>
        <?php } ?>

        <?php $toggleHtml = ob_get_clean();

        if ($this->isInline()) {
            return $this->wrapEmbeddedHtml(
                '',
                labelPrefix: new HtmlString($toggleHtml),
                inlineLabelVerticalAlignment: VerticalAlignment::Center,
            );
        }

        return $this->wrapEmbeddedHtml(
            $toggleHtml,
            inlineLabelVerticalAlignment: VerticalAlignment::Center,
        );
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(BooleanStateCast::class, ['isNullable' => false]),
        ];
    }
}
