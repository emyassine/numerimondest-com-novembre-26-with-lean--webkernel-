<?php

namespace Webkernel\Platform\Widgets\Commands\FileGenerators;

use Webkernel\Platform\Support\Commands\Concerns\CanReadModelSchemas;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Support\Commands\FileGenerators\Contracts\HasSkippedColumns;
use Webkernel\Platform\Tables\Commands\FileGenerators\Concerns\CanGenerateModelTables;
use Webkernel\Platform\Tables\Table;
use Webkernel\Platform\Widgets\TableWidget;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Method;

class TableWidgetClassGenerator extends ClassGenerator implements HasSkippedColumns
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
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
            Table::class,
            ...($this->hasPartialImports() ? ['Webkernel\Platform\Tables'] : []),
            Builder::class,
            $this->getModelFqn(),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return TableWidget::class;
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addTableMethodToClass($class);
    }

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
}
