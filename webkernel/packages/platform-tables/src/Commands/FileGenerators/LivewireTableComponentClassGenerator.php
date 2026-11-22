<?php

namespace Webkernel\Platform\Tables\Commands\FileGenerators;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Support\Commands\Concerns\CanReadModelSchemas;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Support\Commands\FileGenerators\Contracts\HasSkippedColumns;
use Webkernel\Platform\Tables\Commands\FileGenerators\Concerns\CanGenerateModelTables;
use Webkernel\Platform\Tables\Concerns\InteractsWithTable;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;
use Webkernel\View\Contracts\View;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use Webkernel\Liveview\Component;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Literal;
use Webkernel\CodeGenerator\Method;
use Webkernel\CodeGenerator\TraitUse;

class LivewireTableComponentClassGenerator extends ClassGenerator implements HasSkippedColumns
{
    use CanGenerateModelTables;
    use CanReadModelSchemas;

    /**
     * @param  class-string<Model>  $modelFqn
     */
    final public function __construct(
        protected string $fqn,
        protected string $modelFqn,
        protected bool $isGenerated,
        protected string $view,
    ) {}

    public function getNamespace(): string
    {
        return $this->extractNamespace($this->getFqn());
    }

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        return [
            $this->getExtends(),
            ...$this->getImplements(),
            InteractsWithActions::class,
            InteractsWithTable::class,
            InteractsWithSchemas::class,
            Table::class,
            ...($this->hasPartialImports() ? ['Webkernel\Platform\Tables'] : []),
            Builder::class,
            $this->getModelFqn(),
            View::class,
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return Component::class;
    }

    /**
     * @return array<class-string>
     */
    public function getImplements(): array
    {
        return [
            HasActions::class,
            HasSchemas::class,
            HasTable::class,
        ];
    }

    protected function addTraitsToClass(ClassType $class): void
    {
        $this->addInteractsWithActionsTraitToClass($class);
        $this->addInteractsWithTableTraitToClass($class);
        $this->addInteractsWithSchemasTraitToClass($class);
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addTableMethodToClass($class);
        $this->addRenderMethodToClass($class);
    }

    protected function addInteractsWithActionsTraitToClass(ClassType $class): void
    {
        $trait = $class->addTrait(InteractsWithActions::class);
        $this->configureInteractsWithActionsTrait($trait);
    }

    protected function configureInteractsWithActionsTrait(TraitUse $trait): void {}

    protected function addInteractsWithTableTraitToClass(ClassType $class): void
    {
        $trait = $class->addTrait(InteractsWithTable::class);
        $this->configureInteractsWithTableTrait($trait);
    }

    protected function configureInteractsWithTableTrait(TraitUse $trait): void {}

    protected function addInteractsWithSchemasTraitToClass(ClassType $class): void
    {
        $trait = $class->addTrait(InteractsWithSchemas::class);
        $this->configureInteractsWithSchemasTrait($trait);
    }

    protected function configureInteractsWithSchemasTrait(TraitUse $trait): void {}

    protected function addTableMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('table')
            ->setPublic()
            ->setReturnType(Table::class)
            ->setBody($this->generateTableMethodBody($this->getModelFqn()));
        $method->addParameter('table')
            ->setType(Table::class);

        $this->configureTableMethod($method);
    }

    protected function configureTableMethod(Method $method): void {}

    protected function addRenderMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('render')
            ->setPublic()
            ->setReturnType(View::class)
            ->setBody(new Literal(<<<'PHP'
                return view(?);
                PHP, [$this->getView()]));

        $this->configureRenderMethod($method);
    }

    protected function configureRenderMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * @return class-string<Model>
     */
    public function getModelFqn(): string
    {
        return $this->modelFqn;
    }

    public function isGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function getView(): string
    {
        return $this->view;
    }
}
