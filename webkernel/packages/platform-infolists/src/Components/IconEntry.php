<?php

namespace Webkernel\Platform\Infolists\Components;

use BackedEnum;
use Closure;
use Webkernel\Platform\Infolists\View\Components\IconEntryComponent\IconComponent;
use Webkernel\Platform\Infolists\View\InfolistsIconAlias;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\CanWrap;
use Webkernel\Platform\Support\Contracts\HasLabel;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Enums\IconSize;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Collection;
use Webkernel\View\Js;
use Stringable;

use function Webkernel\Platform\Support\generate_href_html;
use function Webkernel\Platform\Support\generate_icon_html;

class IconEntry extends Entry implements HasEmbeddedView
{
    use CanWrap;
    use Concerns\HasColor {
        getColor as getBaseColor;
    }
    use Concerns\HasIcon {
        getIcon as getBaseIcon;
    }

    protected bool | Closure | null $isBoolean = null;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $falseColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $falseIcon = null;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $trueColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $trueIcon = null;

    protected IconSize | string | Closure | null $size = null;

    protected bool | Closure $isListWithLineBreaks = false;

    public function boolean(bool | Closure $condition = true): static
    {
        $this->isBoolean = $condition;

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function false(string | BackedEnum | Htmlable | Closure | false | null $icon = null, string | array | Closure | null $color = null): static
    {
        $this->falseIcon($icon);
        $this->falseColor($color);

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function falseColor(string | array | Closure | null $color): static
    {
        $this->boolean();
        $this->falseColor = $color;

        return $this;
    }

    public function falseIcon(string | BackedEnum | Htmlable | Closure | false | null $icon): static
    {
        $this->boolean();
        $this->falseIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function true(string | BackedEnum | Htmlable | Closure | false | null $icon = null, string | array | Closure | null $color = null): static
    {
        $this->trueIcon($icon);
        $this->trueColor($color);

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function trueColor(string | array | Closure | null $color): static
    {
        $this->boolean();
        $this->trueColor = $color;

        return $this;
    }

    public function trueIcon(string | BackedEnum | Htmlable | Closure | false | null $icon): static
    {
        $this->boolean();
        $this->trueIcon = $icon;

        return $this;
    }

    public function size(IconSize | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(mixed $state, ?Model $relatedRecord = null): IconSize | string | null
    {
        $size = $this->evaluate($this->size, [
            'state' => $state,
            'relatedRecord' => $relatedRecord,
        ]);

        if (is_string($size)) {
            $size = IconSize::tryFrom($size) ?? $size;
        }

        return $size;
    }

    public function getIcon(mixed $state, ?Model $relatedRecord = null): string | BackedEnum | Htmlable | null
    {
        if (filled($icon = $this->getBaseIcon($state, $relatedRecord))) {
            return $icon;
        }

        if (! $this->isBoolean($state, $relatedRecord)) {
            return null;
        }

        if ($state === null) {
            return null;
        }

        return $state ? $this->getTrueIcon($state, $relatedRecord) : $this->getFalseIcon($state, $relatedRecord);
    }

    /**
     * @return string | array<int | string, string | int> | null
     */
    public function getColor(mixed $state, ?Model $relatedRecord = null): string | array | null
    {
        if (filled($color = $this->getBaseColor($state, $relatedRecord))) {
            return $color;
        }

        if (! $this->isBoolean($state, $relatedRecord)) {
            return null;
        }

        if ($state === null) {
            return null;
        }

        return $state ? $this->getTrueColor($state, $relatedRecord) : $this->getFalseColor($state, $relatedRecord);
    }

    /**
     * @return string | array<string>
     */
    public function getFalseColor(mixed $state = null, ?Model $relatedRecord = null): string | array
    {
        return $this->evaluate(
            $this->falseColor,
            $this->getStateEvaluationParameters($state, $relatedRecord, hasState: func_num_args() > 0),
        ) ?? 'danger';
    }

    public function getFalseIcon(mixed $state = null, ?Model $relatedRecord = null): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate(
            $this->falseIcon,
            $this->getStateEvaluationParameters($state, $relatedRecord, hasState: func_num_args() > 0),
        );

        if ($icon === false) {
            return null;
        }

        return $icon
            ?? PlatformIcon::resolve(InfolistsIconAlias::COMPONENTS_ICON_ENTRY_FALSE)
            ?? Heroicon::OutlinedXCircle;
    }

    /**
     * @return string | array<string>
     */
    public function getTrueColor(mixed $state = null, ?Model $relatedRecord = null): string | array
    {
        return $this->evaluate(
            $this->trueColor,
            $this->getStateEvaluationParameters($state, $relatedRecord, hasState: func_num_args() > 0),
        ) ?? 'success';
    }

    public function getTrueIcon(mixed $state = null, ?Model $relatedRecord = null): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate(
            $this->trueIcon,
            $this->getStateEvaluationParameters($state, $relatedRecord, hasState: func_num_args() > 0),
        );

        if ($icon === false) {
            return null;
        }

        return $icon
            ?? PlatformIcon::resolve(InfolistsIconAlias::COMPONENTS_ICON_ENTRY_TRUE)
            ?? Heroicon::OutlinedCheckCircle;
    }

    public function listWithLineBreaks(bool | Closure $condition = true): static
    {
        $this->isListWithLineBreaks = $condition;

        return $this;
    }

    public function isListWithLineBreaks(): bool
    {
        return (bool) $this->evaluate($this->isListWithLineBreaks);
    }

    public function isBoolean(mixed $state = null, ?Model $relatedRecord = null): bool
    {
        if (blank($this->isBoolean)) {
            $record = $this->getRecord();

            $this->isBoolean = ($record instanceof Model) && $record->hasCast($this->getName(), ['bool', 'boolean']);
        }

        return (bool) $this->evaluate(
            $this->isBoolean,
            $this->getStateEvaluationParameters($state, $relatedRecord, hasState: func_num_args() > 0),
        );
    }

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();

        if ($state instanceof Collection) {
            $state = $state->all();
        }

        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-in-icon',
            ]);

        if (blank($state)) {
            $attributes = $attributes
                ->merge([
                    'x-tooltip' => filled($tooltip = $this->getEmptyTooltip())
                        ? '{
                            content: ' . Js::from($tooltip) . ',
                            theme: $store.theme,
                            allowHTML: ' . Js::from($tooltip instanceof Htmlable) . ',
                        }'
                        : null,
                ], escape: false);

            $placeholder = $this->getPlaceholder();

            ob_start(); ?>

            <div <?= $attributes->toHtml() ?>>
                <?php if (filled($placeholder)) { ?>
                    <p class="fi-in-placeholder">
                        <?= e($placeholder) ?>
                    </p>
                <?php } ?>
            </div>

            <?php return $this->wrapEmbeddedHtml(ob_get_clean());
        }

        $state = Arr::wrap($state);
        $relatedRecords = $this->getRelatedRecordsForState($state);

        $alignment = $this->getAlignment();

        $attributes = $attributes
            ->class([
                'fi-in-icon-has-line-breaks' => $this->isListWithLineBreaks(),
                'fi-wrapped' => $this->canWrap(),
                ($alignment instanceof Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : ''),
            ]);

        $shouldOpenUrlInNewTab = $this->shouldOpenUrlInNewTab();

        $formatState = function (mixed $stateItem, ?Model $relatedRecord) use ($shouldOpenUrlInNewTab): string {
            $icon = $this->getIcon($stateItem, $relatedRecord);

            if (blank($icon)) {
                return '';
            }

            $color = $this->getColor($stateItem, $relatedRecord);
            $size = $this->getSize($stateItem, $relatedRecord);
            $tooltip = $this->getTooltip($stateItem, $relatedRecord);

            $item = generate_icon_html($icon, attributes: (new PlatformComponentAttributeBag)
                ->merge([
                    'x-tooltip' => filled($tooltip)
                        ? '{
                            content: ' . Js::from($tooltip) . ',
                            theme: $store.theme,
                            allowHTML: ' . Js::from($tooltip instanceof Htmlable) . ',
                        }'
                        : null,
                ], escape: false)
                ->color(IconComponent::class, $color), size: $size ?? IconSize::Large)
                ->toHtml();

            // The icon and its color are the only value carriers, but `generate_icon_html()` renders a
            // decorative icon with no accessible name and `x-tooltip` is hover-only, so screen readers get
            // nothing. Emit a visually-hidden text alternative (WCAG 1.1.1, 1.4.1) alongside each icon.
            $accessibleText = match (true) {
                filled($tooltip) => $tooltip instanceof Htmlable ? strip_tags($tooltip->toHtml()) : $tooltip,
                $this->isBoolean($stateItem, $relatedRecord) => $stateItem
                    ? __('webkernel-infolists::components.entries.icon.true')
                    : __('webkernel-infolists::components.entries.icon.false'),
                $stateItem instanceof HasLabel => $stateItem->getLabel() ?? (($stateItem instanceof BackedEnum) ? $stateItem->value : ''),
                $stateItem instanceof BackedEnum => $stateItem->value,
                $stateItem instanceof Htmlable => strip_tags($stateItem->toHtml()),
                is_scalar($stateItem) => (string) $stateItem,
                $stateItem instanceof Stringable => (string) $stateItem,
                default => '',
            };

            if (filled($accessibleText)) {
                $item .= '<span class="fi-sr-only">' . e($accessibleText) . '</span>';
            }

            if (filled($url = $this->getUrl($stateItem, $relatedRecord))) {
                $item = '<a ' . generate_href_html($url, $shouldOpenUrlInNewTab)->toHtml() . '>' . $item . '</a>';
            }

            return $item;
        };

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?php foreach ($state as $stateItemIndex => $stateItem) { ?>
                <?= $formatState($stateItem, $relatedRecords[$stateItemIndex] ?? null) ?>
            <?php } ?>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean());
    }

    public function canWrapByDefault(): bool
    {
        return true;
    }
}
