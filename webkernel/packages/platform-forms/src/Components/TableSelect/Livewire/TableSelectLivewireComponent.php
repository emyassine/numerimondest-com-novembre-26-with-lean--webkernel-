<?php

namespace Webkernel\Platform\Forms\Components\TableSelect\Livewire;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Forms\Concerns\InteractsWithForms;
use Webkernel\Platform\Forms\Contracts\HasForms;
use Webkernel\Platform\Support\Services\RelationshipJoiner;
use Webkernel\Platform\Tables\Concerns\InteractsWithTable;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Builder as EloquentBuilder;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\ORM\Relations\MorphToMany;
use Webkernel\ORM\Relations\Relation;
use Webkernel\Database\Query\Builder;
use Webkernel\Liveview\Attributes\Locked;
use Webkernel\Liveview\Attributes\Modelable;
use Webkernel\Liveview\Attributes\Reactive;
use Webkernel\Liveview\Component;
use Webkernel\Liveview\WithoutUrlPagination;
use LogicException;

class TableSelectLivewireComponent extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;
    use WithoutUrlPagination;

    #[Locked]
    #[Reactive]
    public bool $isDisabled = false;

    #[Locked]
    public bool $shouldIgnoreRelatedRecords = false;

    #[Locked]
    public ?int $maxSelectableRecords = null;

    #[Locked]
    public ?string $model = null;

    #[Locked]
    public ?Model $record = null;

    #[Locked]
    public ?string $relationshipName = null;

    #[Locked]
    public string $tableConfiguration;

    /**
     * @var array<mixed>
     */
    #[Locked]
    public array $tableArguments = [];

    /**
     * @var string | array<string> | null
     */
    #[Modelable]
    public string | array | null $state = null;

    public function table(Table $table): Table
    {
        $tableConfiguration = base64_decode($this->tableConfiguration);

        if (! class_exists($tableConfiguration)) {
            throw new LogicException("Table configuration class [{$tableConfiguration}] does not exist.");
        }

        if (! method_exists($tableConfiguration, 'configure')) {
            throw new LogicException("Table configuration class [{$tableConfiguration}] does not have a [configure(Table \$table): Table] method.");
        }

        $tableConfiguration::configure($table);

        $table
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateActions([])
            ->selectable()
            ->trackDeselectedRecords(false)
            ->currentSelectionLivewireProperty('state')
            ->maxSelectableRecords(is_array($this->state) ? $this->maxSelectableRecords : 1)
            ->deselectAllRecordsWhenFiltered(false)
            ->disabledSelection($this->isDisabled)
            ->persistInSession(false)
            ->arguments($this->getTableArguments());

        if (filled($this->relationshipName)) {
            $table->query(function (): EloquentBuilder {
                $relationship = Relation::noConstraints(fn (): Relation => ($this->record ??= app($this->model))->{$this->relationshipName}());

                $relationshipQuery = app(RelationshipJoiner::class)->prepareQueryForNoConstraints($relationship);

                if (! ($relationship instanceof BelongsToMany)) {
                    return $relationshipQuery;
                }

                if ($this->shouldIgnoreRelatedRecords) {
                    $relationshipQuery->whereNotExists(function (Builder $query) use ($relationship): void {
                        $query
                            ->select($relationship->getConnection()->raw(1))
                            ->from($relationship->getTable())
                            ->whereColumn(
                                $relationship->getQualifiedRelatedPivotKeyName(),
                                $relationship->getQualifiedRelatedKeyName(),
                            )
                            ->where(
                                $relationship->getQualifiedForeignPivotKeyName(),
                                $this->record->getAttribute($relationship->getParentKeyName()),
                            );

                        if ($relationship instanceof MorphToMany) {
                            $query->where(
                                $relationship->qualifyPivotColumn($relationship->getMorphType()),
                                $relationship->getMorphClass(),
                            );
                        }
                    });

                    return $relationshipQuery;
                }

                $relationshipBaseQuery = $relationshipQuery->getQuery();

                if (blank($relationshipBaseQuery->joins ?? [])) {
                    return $relationshipQuery;
                }

                array_shift($relationshipBaseQuery->joins);

                return $relationshipQuery;
            });
        }

        return $table;
    }

    /**
     * @return array<mixed>
     */
    public function getTableArguments(): array
    {
        return $this->tableArguments;
    }

    public function render(): string
    {
        return '{{ $this->table }}';
    }
}
