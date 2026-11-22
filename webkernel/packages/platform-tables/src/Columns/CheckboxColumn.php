<?php

namespace Webkernel\Platform\Tables\Columns;

use Webkernel\Platform\Forms\Components\Concerns\HasExtraInputAttributes;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Facades\PlatformAsset;
use Webkernel\Platform\Tables\Columns\Contracts\Editable;
use Webkernel\Platform\Tables\Table;
use Webkernel\View\Htmlable;
use Webkernel\View\Js;

class CheckboxColumn extends Column implements Editable, HasEmbeddedView
{
    // Security: This column saves directly without checking Laravel
    // Model Policies. Use `disabled()` to restrict editing
    // based on your own authorization logic.

    use Concerns\CanBeValidated;
    use Concerns\CanUpdateState;
    use HasExtraInputAttributes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();

        $this->rules(['boolean']);
    }

    public function toEmbeddedHtml(): string
    {
        $isDisabled = $this->isDisabled();
        $state = (bool) $this->getState();
        $relatedRecord = $this->getRelatedRecord();

        $attributes = $this->getExtraAttributeBag()
            ->merge([
                'x-load' => true,
                'x-load-src' => PlatformAsset::getAlpineComponentSrc('columns/checkbox', 'webkernel/platform-tables'),
                'x-data' => 'checkboxTableColumn({
                    messages: ' . Js::from($this->getUpdateStateMessages()) . ',
                    name: ' . Js::from($this->getName()) . ',
                    recordKey: ' . Js::from($this->getRecordKey()) . ',
                    state: ' . Js::from($state) . ',
                })',
                'x-id' => "['table-column-error']",
                'x-on:click.capture' => 'blockEdit($event)',
                'x-on:keydown.capture' => 'blockEdit($event)',
            ], escape: false)
            ->class([
                'fi-ta-checkbox',
                ((($alignment = $this->getAlignment()) instanceof Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : '')),
                'fi-inline' => $this->isInline(),
            ]);

        $inputAttributes = $this->getExtraInputAttributeBag()
            ->merge([
                'aria-label' => e(trim(strip_tags(($ariaLabel = $this->getLabel()) instanceof Htmlable ? $ariaLabel->toHtml() : $ariaLabel)), doubleEncode: false),
                'disabled' => $isDisabled,
                'wire:loading.attr' => 'disabled',
                'wire:target' => implode(',', Table::LOADING_TARGETS),
                'x-bind:aria-invalid' => "error ? 'true' : " . Js::from($this->getExtraInputAttributeBag()->get('aria-invalid')),
                'x-bind:aria-busy' => "isLoading ? 'true' : " . Js::from($this->getExtraInputAttributeBag()->get('aria-busy')),
                'x-bind:aria-describedby' => 'getAccessibilityAttributes(' . Js::from($this->getExtraInputAttributeBag()->only('aria-describedby')->getAttributes()) . ")[ 'aria-describedby' ]",
                'x-bind:disabled' => $isDisabled ? null : 'isLoading',
                'x-on:change' => 'clearError()',
                'x-tooltip' => 'error === undefined ? ' . (filled($tooltip = $this->getTooltip($state, $relatedRecord))
                    ? '{
                        content: ' . Js::from($tooltip) . ',
                        theme: $store.theme,
                        allowHTML: ' . Js::from($tooltip instanceof Htmlable) . ',
                        aria: { content: \'auto\' },
                    }'
                    : 'false') . ' : {
                        content: error,
                        theme: $store.theme,
                        allowHTML: false,
                        aria: { content: null },
                    }',
            ], escape: false)
            ->class([
                'fi-checkbox-input',
            ]);

        ob_start(); ?>

        <div
            x-on:click.stop
            wire:ignore.self
            <?= $attributes->toHtml() ?>
        >
            <input type="hidden" value="<?= $state ? 1 : 0 ?>" x-ref="serverState" />

            <input
                type="checkbox"
                x-bind:class="{
                    'fi-valid': ! error,
                    'fi-invalid': error,
                }"
                x-model="state"
                <?= $inputAttributes->toHtml() ?>
            />

            <p
                x-cloak
                x-show="error"
                x-bind:id="$id('table-column-error')"
                x-ref="error"
                x-text="error"
                class="fi-sr-only"
            ></p>

            <p
                role="status"
                aria-live="polite"
                aria-atomic="true"
                x-text="status"
                class="fi-sr-only"
            ></p>
        </div>

        <?php return ob_get_clean();
    }
}
