<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06;

use Webkernel\Utilities\JsonSchema\Constraints\Factory as BaseFactory;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\AdditionalItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\AdditionalPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\AllOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\AnyOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\ConstConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\ContainsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\DependenciesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\Draft06Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\EnumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\ExclusiveMaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\ExclusiveMinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\FormatConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\ItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MaxItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MaxLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MaxPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MinItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MinLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MinPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\MultipleOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\NotConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\OneOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\PatternConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\PatternPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\PropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\PropertiesNamesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\RefConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\RequiredConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\TypeConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\UniqueItemsConstraint;

class Factory extends BaseFactory
{
    /**
     * @var array<string, class-string>
     */
    protected $constraintMap = [
        'schema' => Draft06Constraint::class,
        'additionalProperties' => AdditionalPropertiesConstraint::class,
        'additionalItems' => AdditionalItemsConstraint::class,
        'dependencies' => DependenciesConstraint::class,
        'type' => TypeConstraint::class,
        'const' => ConstConstraint::class,
        'enum' => EnumConstraint::class,
        'uniqueItems' => UniqueItemsConstraint::class,
        'minItems' => MinItemsConstraint::class,
        'minProperties' => MinPropertiesConstraint::class,
        'maxProperties' => MaxPropertiesConstraint::class,
        'minimum' => MinimumConstraint::class,
        'maximum' => MaximumConstraint::class,
        'exclusiveMinimum' => ExclusiveMinimumConstraint::class,
        'minLength' => MinLengthConstraint::class,
        'maxLength' => MaxLengthConstraint::class,
        'maxItems' => MaxItemsConstraint::class,
        'exclusiveMaximum' => ExclusiveMaximumConstraint::class,
        'multipleOf' => MultipleOfConstraint::class,
        'required' => RequiredConstraint::class,
        'format' => FormatConstraint::class,
        'anyOf' => AnyOfConstraint::class,
        'allOf' => AllOfConstraint::class,
        'oneOf' => OneOfConstraint::class,
        'not' => NotConstraint::class,
        'contains' => ContainsConstraint::class,
        'propertyNames' => PropertiesNamesConstraint::class,
        'patternProperties' => PatternPropertiesConstraint::class,
        'pattern' => PatternConstraint::class,
        'properties' => PropertiesConstraint::class,
        'items' => ItemsConstraint::class,
        'ref' => RefConstraint::class,
    ];
}
