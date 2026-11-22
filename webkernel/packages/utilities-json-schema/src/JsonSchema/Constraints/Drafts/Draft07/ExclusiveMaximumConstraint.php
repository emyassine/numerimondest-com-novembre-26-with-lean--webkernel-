<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class ExclusiveMaximumConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    public function __construct(?Factory $factory = null)
    {
        $this->initialiseErrorBag($factory ?: new Factory());
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'exclusiveMaximum')) {
            return;
        }

        if (!is_numeric($value)) {
            return;
        }

        if ($value < $schema->exclusiveMaximum) {
            return;
        }

        $this->addError(ConstraintError::ExclusiveMaximum, $path, ['exclusiveMaximum' => $schema->exclusiveMaximum, 'found' => $value]);
    }
}
