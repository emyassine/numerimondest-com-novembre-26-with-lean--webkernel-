<?php

namespace Webkernel\Platform\Forms\Components;

use Webkernel\Platform\Schemas\Components\StateCasts\BooleanStateCast;
use Webkernel\Platform\Schemas\Components\StateCasts\Contracts\StateCast;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Enums\VerticalAlignment;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\HtmlString;

class Checkbox extends Field implements HasEmbeddedView
{
    use Concerns\CanBeAccepted;
    use Concerns\CanBeInline;
    use Concerns\CanFixIndistinctState;
    use Concerns\HasExtraInputAttributes;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-forms::components.checkbox';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);

        $this->rule('boolean');
    }

    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();
        $hasError = $this->hasErrorForPath($statePath);

        $attributes = (new PlatformComponentAttributeBag)
            ->merge([
                'autofocus' => $this->isAutofocused(),
                'disabled' => $this->isDisabled(),
                'id' => $this->getId(),
                $this->applyStateBindingModifiers('wire:model') => $statePath,
            ], escape: false)
            ->merge($this->getExtraAttributes(), escape: false)
            ->merge($this->getExtraInputAttributes(), escape: false)
            ->merge([
                ...$this->getAccessibilityAttributes(),
                'required' => $this->isRequired() && (! $this->isDisabled()),
            ], escape: false)
            ->class([
                'fi-checkbox-input',
                'fi-valid' => ! $hasError,
                'fi-invalid' => $hasError,
            ]);

        $inputHtml = '<input type="checkbox" ' . $attributes->toHtml() . ' />';

        if ($this->isInline()) {
            return $this->wrapEmbeddedHtml(
                '',
                labelPrefix: new HtmlString($inputHtml),
                inlineLabelVerticalAlignment: VerticalAlignment::Center,
            );
        }

        return $this->wrapEmbeddedHtml(
            $inputHtml,
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
