<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;
use Webkernel\Utilities\JsonSchema\Tool\EcmaPatternConverter;

class PatternConstraint implements ConstraintInterface
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
        if (!property_exists($schema, 'pattern')) {
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $matchPattern = EcmaPatternConverter::toPcre($schema->pattern);
        if (preg_match($matchPattern, $value) === 1) {
            return;
        }

        $this->addError(ConstraintError::Pattern, $path, ['pattern' => $schema->pattern, 'found' => $value]);
    }
}
