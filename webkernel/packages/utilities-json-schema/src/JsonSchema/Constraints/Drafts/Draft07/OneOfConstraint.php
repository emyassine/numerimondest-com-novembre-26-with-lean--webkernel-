<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class OneOfConstraint implements ConstraintInterface
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
        if (!property_exists($schema, 'oneOf')) {
            return;
        }

        $matchedSchema = 0;
        foreach ($schema->oneOf as $oneOfSchema) {
            $schemaConstraint = $this->factory->createInstanceFor('schema');
            $schemaConstraint->check($value, $oneOfSchema, $path, $i);

            if ($schemaConstraint->isValid()) {
                $matchedSchema++;
                continue;
            }

            $this->addErrors($schemaConstraint->getErrors());
        }

        if ($matchedSchema !== 1) {
            $this->addError(ConstraintError::OneOf, $path);
        } else {
            $this->errorBag()->reset();
        }
    }
}
