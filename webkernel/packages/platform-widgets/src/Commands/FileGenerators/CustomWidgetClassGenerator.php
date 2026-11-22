<?php

namespace Webkernel\Platform\Widgets\Commands\FileGenerators;

use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Support\Commands\FileGenerators\Concerns\CanGenerateViewProperty;
use Webkernel\Platform\Widgets\Widget;
use Webkernel\CodeGenerator\ClassType;

class CustomWidgetClassGenerator extends ClassGenerator
{
    use CanGenerateViewProperty;

    final public function __construct(
        protected string $fqn,
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
        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);

        return [
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return Widget::class;
    }

    protected function addPropertiesToClass(ClassType $class): void
    {
        $this->addViewPropertyToClass($class);
    }

    public function getFqn(): string
    {
        return $this->fqn;
    }

    public function getView(): string
    {
        return $this->view;
    }
}
