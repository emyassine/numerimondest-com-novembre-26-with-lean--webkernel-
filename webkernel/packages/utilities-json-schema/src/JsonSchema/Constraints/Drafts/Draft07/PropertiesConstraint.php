<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class PropertiesConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    /** @var Factory */
    private $factory;

    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
        $this->initialiseErrorBag($this->factory);
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'properties')) {
            return;
        }

        if (!is_object($value)) {
            return;
        }

        $basePath = $path ?? new JsonPointer('');
        foreach ($schema->properties as $propertyName => $propertySchema) {
            $schemaConstraint = $this->factory->createInstanceFor('schema');
            if (!property_exists($value, $propertyName)) {
                continue;
            }

            $incrementedPath = $basePath->withPropertyPaths(array_merge($basePath->getPropertyPaths(), [$propertyName]));

            $schemaConstraint->check($value->{$propertyName}, $propertySchema, $incrementedPath, $i);
            if ($schemaConstraint->isValid()) {
                continue;
            }

            $this->addErrors($schemaConstraint->getErrors());
        }
    }
}
