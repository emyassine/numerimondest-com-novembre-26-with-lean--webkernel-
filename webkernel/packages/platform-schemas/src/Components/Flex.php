<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\HasAlignment;
use Webkernel\Platform\Support\Concerns\HasFromBreakpoint;
use Webkernel\Platform\Support\Concerns\HasVerticalAlignment;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Enums\VerticalAlignment;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\Htmlable;
use Webkernel\View\Js;

class Flex extends Component implements HasEmbeddedView
{
    use EntanglesStateWithSingularRelationship;
    use HasAlignment;
    use HasFromBreakpoint;
    use HasVerticalAlignment;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.flex';

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    final public function __construct(array | Closure $schema)
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    public static function make(array | Closure $schema): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();
        $fromBreakpoint = $this->getFromBreakpoint();
        $verticalAlignment = $this->getVerticalAlignment();
        $alignment = $this->getAlignment();

        if (! $verticalAlignment instanceof VerticalAlignment) {
            $verticalAlignment = filled($verticalAlignment) ? (VerticalAlignment::tryFrom($verticalAlignment) ?? $verticalAlignment) : null;
        }

        if (! $alignment instanceof Alignment) {
            $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
        }

        $attributes = (new PlatformComponentAttributeBag)
            ->merge($this->getExtraAttributes(), escape: false)
            ->class([
                'fi-sc-flex',
                'fi-dense' => $this->isDense(),
                'fi-from-' . ($fromBreakpoint ?? 'default'),
                ($verticalAlignment instanceof VerticalAlignment) ? "fi-vertical-align-{$verticalAlignment->value}" : $verticalAlignment,
                ($alignment instanceof Alignment) ? "fi-align-{$alignment->value}" : $alignment,
            ]);

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?php foreach ($this->getChildSchema()->getComponents() as $schemaComponent) { ?>
                <?php if (($schemaComponent instanceof Action) || ($schemaComponent instanceof ActionGroup)) { ?>
                    <div>
                        <?= $schemaComponent->toHtml() ?>
                    </div>
                <?php } else { ?>
                    <?php
                        $hiddenJs = $schemaComponent->getHiddenJs();
                    $visibleJs = $schemaComponent->getVisibleJs();
                    $schemaComponentStatePath = $schemaComponent->getStatePath();

                    $visibilityJs = match ([filled($hiddenJs), filled($visibleJs)]) {
                        [true, true] => "(! ({$hiddenJs})) && ({$visibleJs})",
                        [true, false] => "! ({$hiddenJs})",
                        [false, true] => $visibleJs,
                        default => null,
                    };
                    ?>
                    <div
                        x-data="platformSchemaComponent({
                                    path: <?= Js::from($schemaComponentStatePath) ?>,
                                    containerPath: <?= Js::from($statePath) ?>,
                                    '$wire': $wire
                                })"
                        <?php if ($afterStateUpdatedJs = $schemaComponent->getAfterStateUpdatedJs()) { ?>
                            x-init="<?= implode(';', array_map(
                                fn (string $js): string => '$wire; $wire.watch(' . Js::from($schemaComponentStatePath) . ', ($state, $old) => isStateChanged($state, $old) && eval(' . Js::from($js) . '))',
                                $afterStateUpdatedJs,
                            )) ?>"
                        <?php } ?>
                        <?php if (filled($visibilityJs)) { ?>
                            x-bind:class="{ 'fi-hidden': ! (<?= $visibilityJs ?>) }"
                            x-cloak
                        <?php } ?>
                        <?php if ($schemaComponent->canGrow()) { ?>
                            class="fi-growable"
                        <?php } ?>
                    >
                        <?= $schemaComponent->toHtml() ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>

        <?php return ob_get_clean();
    }
}
