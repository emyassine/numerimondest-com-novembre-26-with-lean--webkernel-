<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;
use Webkernel\Utilities\JsonSchema\Tool\DeepComparer;

class EnumConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    public function __construct(?Factory $factory = null)
    {
        $this->initialiseErrorBag($factory ?: new Factory());
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'enum')) {
            return;
        }

        foreach ($schema->enum as $enumCase) {
            if (DeepComparer::isEqual($value, $enumCase)) {
                return;
            }

            if (is_numeric($value) && is_numeric($enumCase) && DeepComparer::isEqual((float) $value, (float) $enumCase)) {
                return;
            }
        }

        $this->addError(ConstraintError::Enum, $path, ['enum' => $schema->enum]);
    }
}
