<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class RefConstraint implements ConstraintInterface
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
        if (!property_exists($schema, '$ref')) {
            return;
        }

        try {
            $refSchema = $this->factory->getSchemaStorage()->resolveRefSchema($schema);
        } catch (\Exception $e) {
            return;
        }

        $schemaConstraint = $this->factory->createInstanceFor('schema');
        $schemaConstraint->check($value, $refSchema, $path, $i);

        if ($schemaConstraint->isValid()) {
            return;
        }

        $this->addErrors($schemaConstraint->getErrors());
    }
}
