<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;

class ContainsConstraint implements ConstraintInterface
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
        if (!property_exists($schema, 'contains')) {
            return;
        }

        if (!is_array($value)) {
            return;
        }

        $validElementCount = 0;
        foreach ($value as $propertyValue) {
            $schemaConstraint = $this->factory->createInstanceFor('schema');

            $schemaConstraint->check($propertyValue, $schema->contains, $path, $i);
            if ($schemaConstraint->isValid()) {
                $validElementCount++;
            }
        }

        if (property_exists($schema, 'maxContains') && $validElementCount > $schema->maxContains) {
            $this->addError(ConstraintError::MaxContains, $path, ['maxContains' => $schema->maxContains, 'count' => $validElementCount]);
        }

        $minContains = property_exists($schema, 'minContains') ? $schema->minContains : 1;
        if ($validElementCount < $minContains) {
            $this->addError(ConstraintError::MinContains, $path, ['minContains' => $minContains, 'count' => $validElementCount]);
        }
    }
}
