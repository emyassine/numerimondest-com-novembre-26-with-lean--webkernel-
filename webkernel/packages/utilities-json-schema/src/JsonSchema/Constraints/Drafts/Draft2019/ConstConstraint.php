<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;
use Webkernel\Utilities\JsonSchema\Tool\DeepComparer;

class ConstConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    public function __construct(?Factory $factory = null)
    {
        $this->initialiseErrorBag($factory ?: new Factory());
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'const')) {
            return;
        }

        if (DeepComparer::isEqual($value, $schema->const)) {
            return;
        }

        $this->addError(ConstraintError::Constant, $path, ['const' => $schema->const]);
    }
}
