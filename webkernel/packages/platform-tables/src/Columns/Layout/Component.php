<?php

namespace Webkernel\Platform\Tables\Columns\Layout;

use Closure;
use Webkernel\Platform\Support\Components\ViewComponent;
use Webkernel\Platform\Support\Concerns\CanGrow;
use Webkernel\Platform\Support\Concerns\CanSpanColumns;
use Webkernel\Platform\Support\Concerns\HasExtraAttributes;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\Platform\Tables\Columns\Column;
use Webkernel\Platform\Tables\Columns\Concerns\BelongsToLayout;
use Webkernel\Platform\Tables\Columns\Concerns\BelongsToTable;
use Webkernel\Platform\Tables\Columns\Concerns\CanBeHidden;
use Webkernel\Platform\Tables\Columns\Concerns\HasRecord;
use Webkernel\Platform\Tables\Columns\Concerns\HasRowLoopObject;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Model;
use Webkernel\View\HtmlString;
use LogicException;

class Component extends ViewComponent
{
    use BelongsToLayout;
    use BelongsToTable;
    use CanBeHidden;
    use CanGrow;
    use CanSpanColumns;
    use HasExtraAttributes;
    use HasRecord;
    use HasRowLoopObject;

    protected string $evaluationIdentifier = 'layout';

    protected string $viewIdentifier = 'layout';

    /**
     * @var array<Column | Component> | Closure
     */
    protected array | Closure $components = [];

    protected bool $isCollapsible = false;

    protected bool | Closure $isCollapsed = true;

    /**
     * @param  array<Column | Component> | Closure  $schema
     */
    public function schema(array | Closure $schema): static
    {
        $this->components($schema);

        return $this;
    }

    /**
     * @param  array<Column | Component> | Closure  $components
     */
    public function components(array | Closure $components): static
    {
        $this->components = $components;

        return $this;
    }

    public function collapsible(bool $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    public function collapsed(bool | Closure $condition = true): static
    {
        $this->collapsible();
        $this->isCollapsed = $condition;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return (bool) $this->evaluate($this->isCollapsed);
    }

    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        $columns = [];

        foreach ($this->getComponents() as $component) {
            if ($component instanceof Column) {
                $columns[$component->getName()] = $component;

                continue;
            }

            $columns = [
                ...$columns,
                ...$component->getColumns(),
            ];
        }

        return $columns;
    }

    /**
     * @return array<Column | Component>
     */
    public function getComponents(): array
    {
        return array_map(function (Component | Column $component): Component | Column {
            return $component->layout($this);
        }, $this->evaluate($this->components));
    }

    public function getTable(): Table
    {
        return $this->table ?? $this->getLayout()?->getTable() ?? throw new LogicException('The column layout component is not mounted to a table.');
    }

    public function hasTable(): bool
    {
        return isset($this->table) || ($this->getLayout()?->hasTable() ?? false);
    }

    public function isCollapsible(): bool
    {
        return $this->isCollapsible;
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

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?= $this->toHtml() ?>
        </div>

        <?php return new HtmlString(ob_get_clean());
    }
}
