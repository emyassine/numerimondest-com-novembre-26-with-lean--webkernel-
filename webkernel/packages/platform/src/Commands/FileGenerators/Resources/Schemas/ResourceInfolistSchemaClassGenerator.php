<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Schemas;

use Webkernel\Platform\Commands\FileGenerators\Resources\Concerns\CanGenerateResourceInfolists;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Commands\Concerns\CanReadModelSchemas;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Support\Commands\FileGenerators\Contracts\HasSkippedColumns;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\HasMany;
use Webkernel\Utilities\Arr;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Method;

class ResourceInfolistSchemaClassGenerator extends ClassGenerator implements HasSkippedColumns
{
    use CanGenerateResourceInfolists;
    use CanReadModelSchemas;

    final public function __construct(
        protected string $fqn,
        protected string $modelFqn,
        protected ?string $parentResourceFqn,
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
        return [
            Schema::class,
            ...($this->hasPartialImports() ? ['Webkernel\Platform\Infolists'] : []),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addConfigureMethodToClass($class);
    }

    protected function addConfigureMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('configure')
            ->setPublic()
            ->setStatic()
            ->setReturnType(Schema::class)
            ->setBody($this->generateInfolistMethodBody($this->getModelFqn(), exceptColumns: Arr::wrap($this->getForeignKeyColumnToNotGenerate())));
        $method->addParameter('schema')
            ->setType(Schema::class);

        $this->configureConfigureMethod($method);
    }

    protected function configureConfigureMethod(Method $method): void {}

    public function getForeignKeyColumnToNotGenerate(): ?string
    {
        if (! class_exists($this->getParentResourceFqn())) {
            return null;
        }

        $model = $this->getParentResourceFqn()::getModel();

        if (! class_exists($model)) {
            return null;
        }

        $modelInstance = app($model);
        $relationshipName = (string) str($this->getModelBasename())->plural()->camel();

        if (! method_exists($modelInstance, $relationshipName)) {
            return null;
        }

        $relationship = $modelInstance->{$relationshipName}();

        if (! ($relationship instanceof HasMany)) {
            return null;
        }

        return $relationship->getForeignKeyName();
    }

    public function getModelBasename(): string
    {
        return class_basename($this->getModelFqn());
    }

    /**
     * @return ?class-string
     */
    public function getParentResourceFqn(): ?string
    {
        return $this->parentResourceFqn;
    }

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
