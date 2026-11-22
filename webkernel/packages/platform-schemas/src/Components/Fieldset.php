<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Forms\Components\Concerns\CanBeMarkedAsRequired;
use Webkernel\Platform\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Webkernel\Platform\Schemas\Components\Concerns\HasLabel;
use Webkernel\Platform\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\CanBeContained;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\Htmlable;

class Fieldset extends Component implements CanEntangleWithSingularRelationships, HasEmbeddedView
{
    use CanBeContained;
    use CanBeMarkedAsRequired;
    use EntanglesStateWithSingularRelationship;
    use HasLabel;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.fieldset';

    final public function __construct(string | Htmlable | Closure | null $label = null)
    {
        $this->label($label);
    }

    public static function make(string | Htmlable | Closure | null $label = null): static
    {
        $static = app(static::class, ['label' => $label]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columns(2);
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function toEmbeddedHtml(): string
    {
        $id = $this->getId();
        $isLabelHidden = $this->isLabelHidden();
        $label = $this->getLabel();
        $isContained = $this->isContained();
        $isRequired = $this->isMarkedAsRequired();

        $attributes = (new PlatformComponentAttributeBag)
            ->merge(['id' => $id], escape: false)
            ->merge($this->getExtraAttributes(), escape: false)
            ->class([
                'fi-sc-fieldset',
                'fi-fieldset',
                'fi-fieldset-label-hidden' => $isLabelHidden,
                'fi-fieldset-not-contained' => ! $isContained,
            ]);

        ob_start(); ?>
        <fieldset <?= $attributes->toHtml() ?>>
            <?php if (filled($label)) { ?>
                <legend><?= e($label) ?><?php if ($isRequired) { ?><sup class="fi-fieldset-label-required-mark">*</sup><?php } ?></legend>
            <?php } ?>
            <?= $this->getChildSchema()->toHtml() ?>
        </fieldset>
        <?php return ob_get_clean();
    }
}
