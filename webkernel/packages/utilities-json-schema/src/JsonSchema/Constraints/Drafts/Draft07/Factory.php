<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07;

use Webkernel\Utilities\JsonSchema\Constraints\Factory as BaseFactory;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\AdditionalItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\AdditionalPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\AllOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\AnyOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ConstConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ContainsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ContentConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\DependenciesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\Draft07Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\EnumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ExclusiveMaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ExclusiveMinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\FormatConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\IfThenElseConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\ItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MaxItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MaxLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MaxPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MinItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MinLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MinPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\MultipleOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\NotConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\OneOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\PatternConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\PatternPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\PropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\PropertiesNamesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\RefConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\RequiredConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\TypeConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\UniqueItemsConstraint;

class Factory extends BaseFactory
{
    /**
     * @var array<string, class-string>
     */
    protected $constraintMap = [
        'schema' => Draft07Constraint::class,
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
        'ifThenElse' => IfThenElseConstraint::class,
        'contains' => ContainsConstraint::class,
        'propertyNames' => PropertiesNamesConstraint::class,
        'patternProperties' => PatternPropertiesConstraint::class,
        'pattern' => PatternConstraint::class,
        'properties' => PropertiesConstraint::class,
        'items' => ItemsConstraint::class,
        'ref' => RefConstraint::class,
        'content' => ContentConstraint::class,
    ];
}
