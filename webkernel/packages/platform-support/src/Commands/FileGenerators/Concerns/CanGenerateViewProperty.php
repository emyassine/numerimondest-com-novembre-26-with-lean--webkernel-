<?php

namespace Webkernel\Platform\Support\Commands\FileGenerators\Concerns;

use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Property;

trait CanGenerateViewProperty
{
    protected function addViewPropertyToClass(ClassType $class): void
    {
        $property = $class->addProperty('view', $this->getView())
            ->setProtected()
            ->setType('string');
        $this->configureViewProperty($property);
    }

    protected function configureViewProperty(Property $property): void {}
}
