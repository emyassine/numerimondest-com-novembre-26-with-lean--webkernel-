<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class MinLengthConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    public function __construct(?Factory $factory = null)
    {
        $this->initialiseErrorBag($factory ?: new Factory());
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'minLength')) {
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $length = mb_strlen($value, 'UTF-8');
        if ($length >= $schema->minLength) {
            return;
        }

        $this->addError(ConstraintError::LengthMin, $path, ['minLength' => $schema->minLength, 'found' => $length]);
    }
}
