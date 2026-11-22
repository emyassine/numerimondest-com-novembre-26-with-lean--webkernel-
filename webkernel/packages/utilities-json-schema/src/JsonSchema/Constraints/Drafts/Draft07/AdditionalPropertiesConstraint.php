<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Entity\ErrorBagProxy;
use Webkernel\Utilities\JsonSchema\Entity\JsonPointer;
use Webkernel\Utilities\JsonSchema\Tool\EcmaPatternConverter;

class AdditionalPropertiesConstraint implements ConstraintInterface
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
        if (!property_exists($schema, 'additionalProperties')) {
            return;
        }

        if ($schema->additionalProperties === true) {
            return;
        }

        if (!is_object($value)) {
            return;
        }

        $additionalProperties = get_object_vars($value);

        if (isset($schema->properties)) {
            $additionalProperties = array_diff_key($additionalProperties, (array) $schema->properties);
        }

        if (isset($schema->patternProperties)) {
            $patterns = array_keys(get_object_vars($schema->patternProperties));

            foreach ($additionalProperties as $key => $_) {
                foreach ($patterns as $pattern) {
                    if (preg_match(EcmaPatternConverter::toPcre($pattern), (string) $key)) {
                        unset($additionalProperties[$key]);
                        break;
                    }
                }
            }
        }

        if (is_object($schema->additionalProperties)) {
            foreach ($additionalProperties as $key => $additionalPropertiesValue) {
                $schemaConstraint = $this->factory->createInstanceFor('schema');
                $schemaConstraint->check($additionalPropertiesValue, $schema->additionalProperties, ($path ?? new JsonPointer(''))->withAppendedPath($key), $i);

                // The property is permitted by the schema, so it is never an "additional property"
                // error; what it failed is the sub-schema, and those errors carry the reason.
                if (!$schemaConstraint->isValid()) {
                    $this->addErrors($schemaConstraint->getErrors());
                }

                unset($additionalProperties[$key]);
            }
        }

        foreach ($additionalProperties as $key => $additionalPropertiesValue) {
            $this->addError(ConstraintError::AdditionalProperties, ($path ?? new JsonPointer(''))->withAppendedPath($key), ['found' => $key]);
        }
    }
}
