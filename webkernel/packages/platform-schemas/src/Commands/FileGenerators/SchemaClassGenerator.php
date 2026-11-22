<?php

namespace Webkernel\Platform\Schemas\Commands\FileGenerators;

use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Method;

class SchemaClassGenerator extends ClassGenerator
{
    final public function __construct(
        protected string $fqn,
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
            ->setBody(<<<'PHP'
                return $schema
                    ->components([
                        //
                    ]);
                PHP);
        $method->addParameter('schema')
            ->setType(Schema::class);

        $this->configureConfigureMethod($method);
    }

    protected function configureConfigureMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }
}
