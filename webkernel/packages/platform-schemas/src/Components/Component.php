<?php

namespace Webkernel\Platform\Schemas\Components;

use Webkernel\Platform\Schemas\Components\Concerns\BelongsToContainer;
use Webkernel\Platform\Schemas\Components\Concerns\BelongsToModel;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeDisabled;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeGridContainer;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeHidden;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeLiberatedFromContainerGrid;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeRepeated;
use Webkernel\Platform\Schemas\Components\Concerns\CanPartiallyRender;
use Webkernel\Platform\Schemas\Components\Concerns\CanPoll;
use Webkernel\Platform\Schemas\Components\Concerns\Cloneable;
use Webkernel\Platform\Schemas\Components\Concerns\HasActions;
use Webkernel\Platform\Schemas\Components\Concerns\HasChildComponents;
use Webkernel\Platform\Schemas\Components\Concerns\HasEntryWrapper;
use Webkernel\Platform\Schemas\Components\Concerns\HasFieldWrapper;
use Webkernel\Platform\Schemas\Components\Concerns\HasHeadings;
use Webkernel\Platform\Schemas\Components\Concerns\HasId;
use Webkernel\Platform\Schemas\Components\Concerns\HasInlineLabel;
use Webkernel\Platform\Schemas\Components\Concerns\HasKey;
use Webkernel\Platform\Schemas\Components\Concerns\HasMaxWidth;
use Webkernel\Platform\Schemas\Components\Concerns\HasMeta;
use Webkernel\Platform\Schemas\Components\Concerns\HasState;
use Webkernel\Platform\Schemas\Components\Utilities\Get;
use Webkernel\Platform\Schemas\Components\Utilities\Set;
use Webkernel\Platform\Schemas\Concerns\HasColumns;
use Webkernel\Platform\Schemas\Concerns\HasGap;
use Webkernel\Platform\Schemas\Concerns\HasStateBindingModifiers;
use Webkernel\Platform\Support\Components\ViewComponent;
use Webkernel\Platform\Support\Concerns\CanGrow;
use Webkernel\Platform\Support\Concerns\CanOrderColumns;
use Webkernel\Platform\Support\Concerns\CanSpanColumns;
use Webkernel\Platform\Support\Concerns\HasExtraAttributes;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Arr;
use Webkernel\View\Js;
use Webkernel\View\AttributeBag;
use \Webkernel\View\ComponentSlot;

class Component extends ViewComponent
{
    use BelongsToContainer;
    use BelongsToModel;
    use CanBeDisabled;
    use CanBeGridContainer;
    use CanBeHidden;
    use CanBeLiberatedFromContainerGrid;
    use CanBeRepeated;
    use CanGrow;
    use CanOrderColumns;
    use CanPartiallyRender;
    use CanPoll;
    use CanSpanColumns;
    use Cloneable;
    use HasActions;
    use HasChildComponents;
    use HasColumns;
    use HasEntryWrapper;
    use HasExtraAttributes;
    use HasFieldWrapper;
    use HasGap;
    use HasHeadings;
    use HasId;
    use HasInlineLabel;
    use HasKey;
    use HasMaxWidth;
    use HasMeta;
    use HasState;
    use HasStateBindingModifiers;

    protected string $evaluationIdentifier = 'component';

    protected string $viewIdentifier = 'schemaComponent';

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'context', 'operation' => [$this->getContainer()->getOperation()],
            'get' => [$this->makeGetUtility()],
            'livewire' => [$this->getLivewire()],
            'model' => [$this->getModel()],
            'parentRepeaterItemIndex' => [$this->getParentRepeaterItemIndex()],
            'rawState' => [$this->getRawState()],
            'record' => [$this->getRecord()],
            'set' => [$this->makeSetUtility()],
            'state' => [$this->getState()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        $record = is_a($parameterType, Model::class, allow_string: true) ? $this->getRecord() : null;

        if ((! $record) || is_array($record)) {
            return match ($parameterType) {
                Get::class => [$this->makeGetUtility()],
                Set::class => [$this->makeSetUtility()],
                default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
            };
        }

        return match ($parameterType) {
            Model::class, $record::class => [$record],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraViewData(): array
    {
        return [
            'get' => $this->makeGetUtility(),
            'operation' => $this->getContainer()->getOperation(),
            'record' => $this->getRecord(),
        ];
    }

    /**
     * @internal Do not use this method outside the internals of Platform. It is subject to breaking changes in minor and patch releases.
     */
    public function toSchemaHtml(?bool $isVisible = null): string
    {
        $isVisible ??= $this->isVisible();

        $container = $this->getContainer();

        $isContainerEmbeddedInParentComponent = $container->isEmbeddedInParentComponent();
        $containerParentComponent = $isContainerEmbeddedInParentComponent
            ? $container->getParentComponent()
            : null;
        $containerStatePath = $isContainerEmbeddedInParentComponent
            ? $containerParentComponent->getContainer()->getStatePath()
            : $container->getStatePath();

        /**
         * Instead of only rendering the hidden components, we should
         * render the `<div>` wrappers for all fields, regardless of
         * if they are hidden or not. This is to solve Livewire DOM
         * diffing issues.
         *
         * Additionally, any `<div>` elements that wrap hidden
         * components need to have `class="fi-hidden"`, so that they
         * don't consume grid space.
         */
        $hiddenJs = $this->getHiddenJs();
        $visibleJs = $this->getVisibleJs();

        $visibilityJs = match ([filled($hiddenJs), filled($visibleJs)]) {
            [true, true] => "(! ({$hiddenJs})) && ({$visibleJs})",
            [true, false] => "! ({$hiddenJs})",
            [false, true] => $visibleJs,
            default => null,
        };

        // A component with a client-side visibility condition is wrapped in a `<fieldset>`
        // that is `disabled` while hidden, which bars its controls from native browser
        // validation. Otherwise, the browser would silently block form submission over an
        // invalid control that it cannot focus or anchor a validation bubble to. Unlike
        // concealing containers such as collapsed sections, these components cannot be
        // revealed on demand when they contain an invalid control. The `disabled` attribute
        // is rendered so that the controls are also barred before Alpine.js applies
        // `x-bind:disabled`.
        $wrapperTag = ($isVisible && filled($visibilityJs)) ? 'fieldset' : 'div';

        $maxWidth = $this->getMaxWidth();

        $statePath = $isContainerEmbeddedInParentComponent
            ? $containerParentComponent->getStatePath()
            : $this->getStatePath();

        $key = $this->getKey();

        $attributes = (new PlatformComponentAttributeBag)
            ->when(
                ! $container->isInline(),
                fn (PlatformComponentAttributeBag $attributes) => $attributes->gridColumn($this->getColumnSpan(), $this->getColumnStart(), $this->getColumnOrder(), ! $isVisible),
            )
            ->merge([
                'wire:key' => $this->getLivewireKey(),
                ...(($pollingInterval = $this->getPollingInterval()) ? ["wire:poll.{$pollingInterval}" => "partiallyRenderSchemaComponent('{$this->getKey()}')"] : []),
            ], escape: false)
            ->class([
                ($maxWidth instanceof Width) ? "fi-width-{$maxWidth->value}" : $maxWidth,
                'fi-growable' => $container->isInline() && $this->canGrow(default: false),
                'fi-sc-visibility-fieldset' => $wrapperTag === 'fieldset',
            ]);

        ob_start(); ?>

        <<?= $wrapperTag ?>
            <?php if ($wrapperTag === 'fieldset') { ?>
                disabled
                role="none"
            <?php } ?>
            <?php if (filled($key)) { ?>
                wire:partial="schema-component::<?= $key ?>"
            <?php } ?>
            <?php if ($isVisible) { ?>
                x-data="platformSchemaComponent({
                    path: <?= Js::from($statePath) ?>,
                    containerPath: <?= Js::from($containerStatePath) ?>,
                    '$wire': $wire
                })"
                <?php if ($afterStateUpdatedJs = $this->getAfterStateUpdatedJs()) { ?>
                    x-init="<?= implode(';', array_map(
                        fn (string $js): string => '$wire; $wire.watch(' . Js::from($statePath) . ', ($state, $old) => isStateChanged($state, $old) && eval(' . Js::from($js) . '))',
                        $afterStateUpdatedJs,
                    )) ?>"
                <?php } ?>
                <?php if (filled($visibilityJs)) { ?>
                    x-bind:class="{ 'fi-hidden': ! (<?= $visibilityJs ?>) }"
                    x-bind:disabled="! (<?= $visibilityJs ?>)"
                    x-cloak
                <?php } ?>
            <?php } ?>
            <?= $attributes->toHtml() ?>
        >
            <?php if ($isVisible) { ?>
                <div
                    class="<?= Arr::toCssClasses([
                        'fi-sc-component',
                        'fi-grid-ctn' => $this->isGridContainer(),
                    ]) ?>"
                >
                    <?= $this->toHtml() ?>
                </div>
            <?php } ?>
        </<?= $wrapperTag ?>>

        <?php return ob_get_clean();
    }

    /**
     * @internal This method is not part of the public API and should not be used. Its parameters may change at any time without notice.
     */
    protected function renderWrapperBladeComponent(string $component, ComponentSlot $slot, PlatformComponentAttributeBag $attributes): string
    {
        // Wrappers were originally rendered using the `<x-dynamic-component>` Blade component,
        // so `fieldWrapperView()` and `entryWrapperView()` accepted any Blade component name,
        // which is still supported for backwards compatibility.
        $slot_html = method_exists($slot, 'toHtml') ? $slot->toHtml() : (string) $slot;
        $attribute_values = method_exists($attributes, 'getAttributes')
            ? $attributes->getAttributes()
            : [];

        return \Webkernel\View\View::renderString(<<<'HTML'
            @component($component, ['__attribute_spreads' => [$attributes]])
                {!! $slot !!}
            @endcomponent
        HTML, [
            'component' => $component,
            'attributes' => new \Webkernel\View\AttributeBag($attribute_values),
            'slot' => \Webkernel\View\View::html($slot_html),
        ]);
    }

    /**
     * @deprecated Fields no longer strip their native validation attributes when concealed. Concealing containers reveal themselves around an invalid control, using the `invalid` event listener in the `webkernel/platform-schemas` package, and components with a client-side visibility condition are wrapped in a `<fieldset>` that is `disabled` while hidden.
     */
    public function isConcealed(): bool
    {
        return false;
    }
}
