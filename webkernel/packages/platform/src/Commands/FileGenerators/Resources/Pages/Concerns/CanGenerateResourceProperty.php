<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Pages\Concerns;

use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Literal;
use Webkernel\CodeGenerator\Property;

trait CanGenerateResourceProperty
{
    protected function addResourcePropertyToClass(ClassType $class): void
    {
        $property = $class->addProperty('resource', new Literal("{$this->simplifyFqn($this->getResourceFqn())}::class"))
            ->setProtected()
            ->setStatic()
            ->setType('string');
        $this->configureResourceProperty($property);
    }

    protected function configureResourceProperty(Property $property): void {}
}
