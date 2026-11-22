<?php

namespace Webkernel\Platform\Tables\Columns;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Support\Components\ViewComponent;
use Webkernel\Platform\Support\Concerns\CanAggregateRelatedModels;
use Webkernel\Platform\Support\Concerns\CanGrow;
use Webkernel\Platform\Support\Concerns\CanSpanColumns;
use Webkernel\Platform\Support\Concerns\CanWrapHeader;
use Webkernel\Platform\Support\Concerns\HasAlignment;
use Webkernel\Platform\Support\Concerns\HasCellState;
use Webkernel\Platform\Support\Concerns\HasExtraAttributes;
use Webkernel\Platform\Support\Concerns\HasPlaceholder;
use Webkernel\Platform\Support\Concerns\HasRelatedRecord;
use Webkernel\Platform\Support\Concerns\HasVerticalAlignment;
use Webkernel\Platform\Support\Concerns\HasWidth;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Enums\VerticalAlignment;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\Platform\Tables\Columns\Concerns\HasTooltip;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Model;
use Webkernel\View\HtmlString;
use LogicException;

use function Webkernel\Platform\Support\generate_href_html;

class Column extends ViewComponent
{
    use CanAggregateRelatedModels;
    use CanGrow;
    use CanSpanColumns;
    use CanWrapHeader;
    use Concerns\BelongsToGroup;
    use Concerns\BelongsToLayout;
    use Concerns\BelongsToTable;
    use Concerns\CanBeDisabled;
    use Concerns\CanBeHidden;
    use Concerns\CanBeInline;
    use Concerns\CanBeSearchable;
    use Concerns\CanBeSortable;
    use Concerns\CanBeSummarized;
    use Concerns\CanBeToggled;
    use Concerns\CanCallAction;
    use Concerns\CanOpenUrl;
    use Concerns\HasExtraCellAttributes;
    use Concerns\HasExtraHeaderAttributes;
    use Concerns\HasLabel;
    use Concerns\HasName;
    use Concerns\HasRecord;
    use Concerns\HasRowLoopObject;
    use Concerns\InteractsWithTableQuery;
    use HasAlignment;
    use HasCellState;
    use HasExtraAttributes;
    use HasPlaceholder;
    use HasRelatedRecord;
    use HasTooltip;
    use HasVerticalAlignment;
    use HasWidth;

    protected string $evaluationIdentifier = 'column';

    protected string $viewIdentifier = 'column';

    protected ?string $cachedCellAttributeHtml = null;

    final public function __construct(string $name)
    {
        $this->name($name);
    }

    public static function make(?string $name = null): static
    {
        $columnClass = static::class;

        $name ??= static::getDefaultName();

        if (blank($name)) {
            throw new LogicException("Column of class [$columnClass] must have a unique name, passed to the [make()] method.");
        }

        $static = app($columnClass, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public static function getDefaultName(): ?string
    {
        return null;
    }

    public function getTable(): Table
    {
        return $this->table ?? $this->getGroup()?->getTable() ?? $this->getLayout()?->getTable() ?? throw new LogicException("The column [{$this->getName()}] is not mounted to a table.");
    }

    public function hasTable(): bool
    {
        return isset($this->table) || ($this->getGroup()?->hasTable() ?? false) || ($this->getLayout()?->hasTable() ?? false);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            'record' => [$this->getRecord()],
            'rowLoop' => [$this->getRowLoop()],
            'state' => [$this->getState()],
            'table' => [$this->getTable()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        $record = is_a($parameterType, Model::class, allow_string: true) ? $this->getRecord() : null;

        if (! $record) {
            return parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType);
        }

        if (! ($record instanceof Model)) {
            return parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType);
        }

        return match ($parameterType) {
            Model::class, $record::class => [$record],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }

    public function renderInLayout(): ?HtmlString
    {
        if ($this->isHidden()) {
            return null;
        }

        $attributes = (new PlatformComponentAttributeBag)
            ->gridColumn(
                $this->getColumnSpan(),
                $this->getColumnStart(),
            )
            ->class([
                'fi-growable' => $this->canGrow(),
                (filled($hiddenFrom = $this->getHiddenFrom()) ? "{$hiddenFrom}:fi-hidden" : ''),
                (filled($visibleFrom = $this->getVisibleFrom()) ? "{$visibleFrom}:fi-visible" : ''),
            ]);

        $this->inline();

        $action = $this->getAction();
        $url = $this->getUrl();
        $isClickDisabled = $this->isClickDisabled();

        $wrapperTag = match (true) {
            $url && (! $isClickDisabled) => 'a',
            $action && (! $isClickDisabled) => 'button',
            default => 'div',
        };

        $attributes = $attributes
            ->merge([
                'type' => ($wrapperTag === 'button') ? 'button' : null,
                'wire:click.prevent.stop' => $wireClickAction = match (true) {
                    ($wrapperTag !== 'button') => null,
                    $action instanceof Action => "mountTableAction('{$action->getName()}', '{$this->getRecordKey()}')",
                    filled($action) => "callTableColumnAction('{$this->getName()}', '{$this->getRecordKey()}')",
                    default => null,
                },
                'wire:loading.attr' => ($wrapperTag === 'button') ? 'disabled' : null,
                'wire:target' => $wireClickAction,
            ], escape: false)
            ->class([
                'fi-ta-col',
                ((($alignment = $this->getAlignment()) instanceof Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : '')),
                'fi-ta-col-has-column-url' => ($wrapperTag === 'a') && filled($url),
            ]);

        ob_start(); ?>

        <<?= $wrapperTag ?>
            <?php if ($wrapperTag === 'a') {
                echo generate_href_html($url, $this->shouldOpenUrlInNewTab())->toHtml();
            } ?>
            <?= $attributes->toHtml() ?>
        >
            <?= $this->toHtml() ?>
        </<?= $wrapperTag ?>>

        <?php return new HtmlString(ob_get_clean());
    }

    public function getCellAttributeHtml(): string
    {
        $columnAlignment = $this->getAlignment();
        $columnVerticalAlignment = $this->getVerticalAlignment();

        return $this->getExtraCellAttributeBag()->class([
            'fi-ta-cell',
            'fi-ta-cell-' . str($this->getName())->camel()->kebab(),
            (($columnAlignment instanceof Alignment) ? "fi-align-{$columnAlignment->value}" : (is_string($columnAlignment) ? $columnAlignment : '')),
            (($columnVerticalAlignment instanceof VerticalAlignment) ? "fi-vertical-align-{$columnVerticalAlignment->value}" : (is_string($columnVerticalAlignment) ? $columnVerticalAlignment : '')),
            (filled($columnHiddenFrom = $this->getHiddenFrom()) ? "{$columnHiddenFrom}:fi-hidden" : ''),
            (filled($columnVisibleFrom = $this->getVisibleFrom()) ? "{$columnVisibleFrom}:fi-visible" : ''),
        ])->toHtml();
    }

    public function getCachedCellAttributeHtml(): string
    {
        if ($this->cachedCellAttributeHtml !== null) {
            return $this->cachedCellAttributeHtml;
        }

        if (
            $this->hasDynamicExtraCellAttributes()
            || $this->hasDynamicAlignment()
            || $this->hasDynamicVerticalAlignment()
            || $this->hasDynamicHiddenFrom()
            || $this->hasDynamicVisibleFrom()
        ) {
            return $this->getCellAttributeHtml();
        }

        return $this->cachedCellAttributeHtml = $this->getCellAttributeHtml();
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
