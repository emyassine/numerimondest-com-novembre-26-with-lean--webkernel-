<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Utilities\JsonSchema\Constraints;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

/**
 * The StringConstraint Constraints, validates an string against a given schema
 *
 */
class StringConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Verify maxLength
        if (isset($schema->maxLength) && $this->strlen($element) > $schema->maxLength) {
            $this->addError(ConstraintError::LengthMax, $path, [
                'maxLength' => $schema->maxLength,
            ]);
        }

        //verify minLength
        if (isset($schema->minLength) && $this->strlen($element) < $schema->minLength) {
            $this->addError(ConstraintError::LengthMin, $path, [
                'minLength' => $schema->minLength,
            ]);
        }

        // Verify a regex pattern
        if (isset($schema->pattern) && !preg_match(self::jsonPatternToPhpRegex($schema->pattern), $element)) {
            $this->addError(ConstraintError::Pattern, $path, [
                'pattern' => $schema->pattern,
            ]);
        }

        $this->checkFormat($element, $schema, $path, $i);
    }

    private function strlen($string)
    {
        if (extension_loaded('mbstring')) {
            return mb_strlen($string, 'UTF-8');
        }

        // mbstring is present on all test platforms, so strlen() can be ignored for coverage
        return strlen($string); // @codeCoverageIgnore
    }
}
