<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Pages;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\EditAction;
use Webkernel\Platform\Commands\FileGenerators\Concerns\CanGenerateGetHeaderActionsMethod;
use Webkernel\Platform\Commands\FileGenerators\Resources\Pages\Concerns\CanGenerateResourceProperty;
use Webkernel\Platform\Resources\Pages\ViewRecord;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\CodeGenerator\ClassType;

class ResourceViewRecordPageClassGenerator extends ClassGenerator
{
    use CanGenerateGetHeaderActionsMethod;
    use CanGenerateResourceProperty;

    /**
     * @param  class-string  $resourceFqn
     */
    final public function __construct(
        protected string $fqn,
        protected string $resourceFqn,
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
        return ViewRecord::class;
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
            EditAction::class,
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
}
