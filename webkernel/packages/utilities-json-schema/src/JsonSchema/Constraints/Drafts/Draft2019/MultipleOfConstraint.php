<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class MultipleOfConstraint implements ConstraintInterface
{
    use ErrorBagProxy;

    public function __construct(?Factory $factory = null)
    {
        $this->initialiseErrorBag($factory ?: new Factory());
    }

    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'multipleOf')) {
            return;
        }

        if ((!is_int($schema->multipleOf) && !is_float($schema->multipleOf)) || $schema->multipleOf <= 0.0) {
            return;
        }

        if (!is_int($value) && !is_float($value)) {
            return;
        }

        if ($this->isMultipleOf($value, $schema->multipleOf)) {
            return;
        }

        $this->addError(ConstraintError::MultipleOf, $path, ['multipleOf' => $schema->multipleOf, 'found' => $value]);
    }

    /**
     * @param int|float $number1
     * @param int|float $number2
     */
    private function isMultipleOf($number1, $number2): bool
    {
        $modulus = ($number1 - round($number1 / $number2) * $number2);
        $precision = 0.0000000001;

        return -$precision < $modulus && $modulus < $precision;
    }
}
