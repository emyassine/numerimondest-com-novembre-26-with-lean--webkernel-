<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Pages;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\DeleteAction;
use Webkernel\Platform\Actions\ForceDeleteAction;
use Webkernel\Platform\Actions\RestoreAction;
use Webkernel\Platform\Actions\ViewAction;
use Webkernel\Platform\Commands\FileGenerators\Concerns\CanGenerateGetHeaderActionsMethod;
use Webkernel\Platform\Commands\FileGenerators\Resources\Pages\Concerns\CanGenerateResourceProperty;
use Webkernel\Platform\Resources\Pages\EditRecord;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\CodeGenerator\ClassType;

class ResourceEditRecordPageClassGenerator extends ClassGenerator
{
    use CanGenerateGetHeaderActionsMethod;
    use CanGenerateResourceProperty;

    /**
     * @param  class-string  $resourceFqn
     */
    final public function __construct(
        protected string $fqn,
        protected string $resourceFqn,
        protected bool $hasViewOperation,
        protected bool $isSoftDeletable,
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
        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);

        return [
            $this->getResourceFqn(),
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
            ...($this->hasPartialImports() ? [
                'Webkernel\Platform\Actions',
            ] : $this->getHeaderActions()),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return EditRecord::class;
    }

    protected function addPropertiesToClass(ClassType $class): void
    {
        $this->addResourcePropertyToClass($class);
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addGetHeaderActionsMethodToClass($class);
    }

    /**
     * @return array<class-string<Action>>
     */
    public function getHeaderActions(): array
    {
        return [
            ...($this->hasViewOperation() ? [ViewAction::class] : []),
            DeleteAction::class,
            ...($this->isSoftDeletable() ? [
                ForceDeleteAction::class,
                RestoreAction::class,
            ] : []),
        ];
    }

    public function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * @return class-string
     */
    public function getResourceFqn(): string
    {
        return $this->resourceFqn;
    }

    public function hasViewOperation(): bool
    {
        return $this->hasViewOperation;
    }

    public function isSoftDeletable(): bool
    {
        return $this->isSoftDeletable;
    }
}
