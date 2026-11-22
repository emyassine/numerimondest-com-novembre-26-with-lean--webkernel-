<?php

namespace Webkernel\Platform\Widgets\Commands\FileGenerators;

use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Widgets\StatsOverviewWidget;
use Webkernel\Platform\Widgets\StatsOverviewWidget\Stat;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Method;

class StatsOverviewWidgetClassGenerator extends ClassGenerator
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
        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);

        return [
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
            Stat::class,
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return StatsOverviewWidget::class;
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addGetStatsMethodToClass($class);
    }

    protected function addGetStatsMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('getStats')
            ->setProtected()
            ->setReturnType('array')
            ->setBody(<<<'PHP'
                return [
                    //
                ];
                PHP);

        $this->configureGetStatsMethod($method);
    }

    protected function configureGetStatsMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }
}
