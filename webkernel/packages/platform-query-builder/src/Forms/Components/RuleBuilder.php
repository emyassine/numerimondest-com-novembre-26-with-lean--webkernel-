<?php

namespace Webkernel\Platform\QueryBuilder\Forms\Components;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\Components\Builder;
use Webkernel\Platform\Forms\Components\Repeater;
use Webkernel\Platform\Forms\View\FormsIconAlias;
use Webkernel\Platform\QueryBuilder\Concerns\HasConstraints;
use Webkernel\Platform\QueryBuilder\Constraints\Constraint;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Flex;
use Webkernel\Platform\Schemas\Components\Utilities\Get;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Enums\Size;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Utilities\Str;

class RuleBuilder extends Builder
{
    use HasConstraints;

    public const OR_BLOCK_NAME = 'or';

    public const OR_BLOCK_GROUPS_REPEATER_NAME = 'groups';

    protected int | Closure | null $maxRules = null;

    protected int | Closure | null $maxNestingDepth = null;

    protected int $nestingDepth = 1;

    protected ?RuleBuilder $rootRuleBuilder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('webkernel-query-builder::query-builder.form.rules.label'))
            ->blocks(function (): array {
                $blocks = array_map(
                    static fn (Constraint | array $constraints): Builder\Block | array => is_array($constraints)
                        ? array_map(static fn (Constraint $constraint): Builder\Block => $constraint->getBuilderBlock(), $constraints)
                        : $constraints->getBuilderBlock(),
                    $this->getGroupedConstraints(),
                );

                $orBlock = Builder\Block::make(static::OR_BLOCK_NAME)
                    ->label(__('webkernel-query-builder::query-builder.form.or_groups.block.label'))
                    ->icon(PlatformIcon::resolve(QueryBuilderIconAlias::OR_GROUP_BLOCK) ?? Heroicon::Slash)
                    ->schema([
                        Flex::make(function (Flex $component): array {
                            /** @var RuleBuilder $builder */
                            $builder = $component->getContainer()->getParentComponent()->getContainer()->getParentComponent();

                            return [
                                Repeater::make(static::OR_BLOCK_GROUPS_REPEATER_NAME)
                                    ->label(__('webkernel-query-builder::query-builder.form.or_groups.label'))
                                    ->schema([
                                        Flex::make([
                                            static::make('rules')
                                                ->hiddenLabel()
                                                ->partiallyRenderAfterActionsCalled($builder->shouldPartiallyRenderAfterActionsCalled())
                                                ->constraints($this->getGroupedConstraints())
                                                ->blockPickerColumns($this->getBlockPickerColumns())
                                                ->blockPickerWidth($this->getBlockPickerWidth())
                                                ->maxRules($this->getMaxRules())
                                                ->maxNestingDepth($this->getMaxNestingDepth())
                                                ->nestingDepth($this->getNestingDepth() + 1)
                                                ->rootRuleBuilder($this->getRootRuleBuilder()),
                                            Actions::make(function (Actions $component): array {
                                                /** @var Repeater $repeater */
                                                $repeater = $component->getContainer()->getParentComponent()->getContainer()->getParentComponent();

                                                return [
                                                    Action::make($deleteActionName = $repeater->getDeleteActionName())
                                                        ->label(__('webkernel-forms::components.repeater.actions.delete.label'))
                                                        ->icon(PlatformIcon::resolve(FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE) ?? Heroicon::Trash)
                                                        ->color('danger')
                                                        ->iconButton()
                                                        ->size(Size::Small)
                                                        ->action($repeater->getAction($deleteActionName)(['item' => (string) str($component->getContainer()->getParentComponent()->getContainer()->getStatePath(isAbsolute: false))->beforeLast('.data')])->getLivewireClickHandler())
                                                        ->visible(fn (Get $get): bool => blank($get('rules')) && (count($repeater->getRawState()) > 2)),
                                                ];
                                            })->grow(false),
                                        ])
                                            ->verticallyAlignCenter()
                                            ->extraAttributes([
                                                'role' => 'group',
                                                'aria-label' => __('webkernel-query-builder::query-builder.form.or_groups.group.label'),
                                            ]),
                                    ])
                                    ->addAction(fn (Action $action, Repeater $component) => $action
                                        ->label(__('webkernel-query-builder::query-builder.actions.add_rule_group.label'))
                                        ->icon(PlatformIcon::resolve(QueryBuilderIconAlias::OR_GROUP_ADD_GROUP_ACTION) ?? Heroicon::Plus)
                                        ->hidden(fn (): bool => filled(array_filter($component->getRawState(), fn (array $itemState): bool => blank($itemState['rules']))))
                                        ->disabled(fn (): bool => $this->isAtRuleLimit())
                                        ->tooltip(fn (): ?string => $this->getRuleLimitReachedTooltip()))
                                    ->addActionAlignment(Alignment::End)
                                    ->labelBetweenItems(__('webkernel-query-builder::query-builder.item_separators.or'))
                                    ->itemHeaders(false)
                                    ->defaultItems(2)
                                    ->minItems(2)
                                    ->cloneable()
                                    ->cloneAction(fn (Action $action) => $action
                                        ->disabled(fn (): bool => $this->isAtRuleLimit())
                                        ->tooltip(fn (): ?string => $this->getRuleLimitReachedTooltip()))
                                    ->hiddenLabel()
                                    ->generateUuidUsing(fn (): string => Str::random(4))
                                    ->partiallyRenderAfterActionsCalled($builder->shouldPartiallyRenderAfterActionsCalled()),
                                Actions::make([
                                    Action::make($cloneActionName = $builder->getCloneActionName())
                                        ->label(__('webkernel-forms::components.builder.actions.clone.label'))
                                        ->icon(PlatformIcon::resolve(FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_CLONE) ?? Heroicon::Square2Stack)
                                        ->color('gray')
                                        ->iconButton()
                                        ->size(Size::Small)
                                        ->disabled(fn (): bool => $builder->isAtRuleLimit())
                                        ->tooltip(fn (): ?string => $builder->getRuleLimitReachedTooltip())
                                        ->action($builder->getAction($cloneActionName)(['item' => (string) str($component->getContainer()->getStatePath(isAbsolute: false))->beforeLast('.data')])->getLivewireClickHandler()),
                                    Action::make($deleteActionName = $builder->getDeleteActionName())
                                        ->label(__('webkernel-forms::components.builder.actions.delete.label'))
                                        ->icon(PlatformIcon::resolve(FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_DELETE) ?? Heroicon::Trash)
                                        ->color('danger')
                                        ->iconButton()
                                        ->size(Size::Small)
                                        ->action($builder->getAction($deleteActionName)(['item' => (string) str($component->getContainer()->getStatePath(isAbsolute: false))->beforeLast('.data')])->getLivewireClickHandler()),
                                ])->grow(false),
                            ];
                        })
                            // Name the whole OR condition as a group so screen readers convey the boundary of
                            // each OR-joined branch of the query (WCAG 1.3.1).
                            ->extraAttributes([
                                'role' => 'group',
                                'aria-label' => __('webkernel-query-builder::query-builder.form.or_groups.block.label'),
                            ]),
                    ]);

                $blocks[] = $orBlock;

                return $blocks;
            })
            ->addAction(fn (Action $action) => $action
                ->label(__('webkernel-query-builder::query-builder.actions.add_rule.label'))
                ->icon(PlatformIcon::resolve(QueryBuilderIconAlias::ADD_RULE_ACTION) ?? Heroicon::Plus)
                ->disabled(fn (): bool => $this->isAtRuleLimit())
                ->tooltip(fn (): ?string => $this->getRuleLimitReachedTooltip()))
            ->addBetweenAction(fn (Action $action) => $action->hidden())
            ->addActionAlignment(Alignment::Start)
            ->labelBetweenItems(__('webkernel-query-builder::query-builder.item_separators.and'))
            ->blockHeaders(false)
            ->cloneable()
            ->cloneAction(fn (Action $action) => $action
                ->disabled(fn (): bool => $this->isAtRuleLimit())
                ->tooltip(fn (): ?string => $this->getRuleLimitReachedTooltip()))
            ->generateUuidUsing(fn (): string => Str::random(4))
            ->partiallyRenderAfterActionsCalled(false)
            ->extraAttributes(['class' => 'fi-fo-builder-not-contained']);
    }

    protected function getBlockGroup(Builder\Block $block): ?string
    {
        if (($block->getName() === static::OR_BLOCK_NAME) && filled($this->blockGroups)) {
            return __('webkernel-query-builder::query-builder.form.or_groups.block.picker_group');
        }

        return parent::getBlockGroup($block);
    }

    public function getConstraint(string $name): ?Constraint
    {
        return $this->getConstraints()[$name] ?? null;
    }

    /**
     * @param  array<string, ?int> | int | null  $columns
     */
    public function constraintPickerColumns(array | int | null $columns = 2): static
    {
        $this->blockPickerColumns($columns);

        return $this;
    }

    public function maxRules(int | Closure | null $count): static
    {
        $this->maxRules = $count;

        return $this;
    }

    public function getMaxRules(): ?int
    {
        $count = $this->evaluate($this->maxRules);

        return ($count === null) ? null : (int) $count;
    }

    public function maxNestingDepth(int | Closure | null $depth): static
    {
        $this->maxNestingDepth = $depth;

        return $this;
    }

    public function getMaxNestingDepth(): ?int
    {
        $depth = $this->evaluate($this->maxNestingDepth);

        return ($depth === null) ? null : (int) $depth;
    }

    public function nestingDepth(int $depth): static
    {
        $this->nestingDepth = $depth;

        return $this;
    }

    public function getNestingDepth(): int
    {
        return $this->nestingDepth;
    }

    public function rootRuleBuilder(RuleBuilder $ruleBuilder): static
    {
        $this->rootRuleBuilder = $ruleBuilder;

        return $this;
    }

    public function getRootRuleBuilder(): RuleBuilder
    {
        return $this->rootRuleBuilder ?? $this;
    }

    public function canAddOrBlock(): bool
    {
        $maxNestingDepth = $this->getMaxNestingDepth();

        if ($maxNestingDepth === null) {
            return true;
        }

        // An "OR" block nests its rules one level deeper than the current builder, so it is only offered while that deeper level stays within the limit.
        return $this->getNestingDepth() < $maxNestingDepth;
    }

    /**
     * @return array<Builder\Block>
     */
    public function getBlockPickerBlocks(): array
    {
        // Security: The picker dropdown opens from its wrapper regardless of the trigger button's disabled state, so removing the pickable blocks is what actually prevents adding rules. Offer nothing once the tree is at the `maxRules()` limit, and drop the "OR" block, which nests one level deeper, once `maxNestingDepth()` would be exceeded. The disabled state and tooltip on the add button remain purely for feedback.
        if ($this->isAtRuleLimit()) {
            return [];
        }

        $blocks = parent::getBlockPickerBlocks();

        if ($this->canAddOrBlock()) {
            return $blocks;
        }

        return array_filter(
            $blocks,
            fn (Builder\Block $block): bool => $block->getName() !== static::OR_BLOCK_NAME,
        );
    }

    public function getTreeRuleCount(): int
    {
        return $this->countTreeRules($this->getRootRuleBuilder()->getRawState() ?? []);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function countTreeRules(array $rules): int
    {
        $count = 0;

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            // Only leaf conditions count towards the limit; "OR" blocks and their groups are structural containers, so the count matches the number of conditions the user sees. Nesting is bounded separately by `maxNestingDepth()`.
            if (($rule['type'] ?? null) === static::OR_BLOCK_NAME) {
                foreach ($rule['data'][static::OR_BLOCK_GROUPS_REPEATER_NAME] ?? [] as $orGroup) {
                    $count += $this->countTreeRules($orGroup['rules'] ?? []);
                }

                continue;
            }

            $count++;
        }

        return $count;
    }

    public function isAtRuleLimit(): bool
    {
        $maxRules = $this->getMaxRules();

        if ($maxRules === null) {
            return false;
        }

        return $this->getTreeRuleCount() >= $maxRules;
    }

    public function getRuleLimitReachedTooltip(): ?string
    {
        if (! $this->isAtRuleLimit()) {
            return null;
        }

        return __('webkernel-query-builder::query-builder.max_rules_reached_tooltip', ['count' => $this->getMaxRules()]);
    }
}
