<?php

namespace Webkernel\Platform\Forms\Commands\FileGenerators;

use Webkernel\Platform\Forms\Commands\FileGenerators\Concerns\CanGenerateModelForms;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Commands\Concerns\CanReadModelSchemas;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Support\Commands\FileGenerators\Contracts\HasSkippedColumns;
use Webkernel\ORM\Model;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Method;

class FormSchemaClassGenerator extends ClassGenerator implements HasSkippedColumns
{
    use CanGenerateModelForms;
    use CanReadModelSchemas;

    /**
     * @param  ?class-string<Model>  $modelFqn
     */
    final public function __construct(
        protected string $fqn,
        protected ?string $modelFqn,
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
            ...($this->hasPartialImports() ? [
                'Webkernel\Platform\Forms',
                'Webkernel\Platform\Schemas',
            ] : []),
            ...(($modelFqn = $this->getModelFqn()) ? [$modelFqn] : []),
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
        $modelFqn = $this->getModelFqn();

        $method = $class->addMethod('configure')
            ->setPublic()
            ->setStatic()
            ->setReturnType(Schema::class)
            ->setBody(
                filled($modelFqn)
                ? $this->generateFormMethodBody($this->getModelFqn())
                : <<<'PHP'
                    return $schema
                        ->components([
                            //
                        ]);
                    PHP,
            );
        $method->addParameter('schema')
            ->setType(Schema::class);

        $this->configureConfigureMethod($method);
    }

    protected function configureConfigureMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * @return ?class-string<Model>
     */
    public function getModelFqn(): ?string
    {
        return $this->modelFqn;
    }
}
