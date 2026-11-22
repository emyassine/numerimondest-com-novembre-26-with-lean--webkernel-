<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\Constraints\Factory as BaseFactory;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\AdditionalItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\AdditionalPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\AllOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\AnyOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ConstConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ContainsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ContentConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\DependentRequiredConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\DependentSchemasConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\Draft2019Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\EnumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ExclusiveMaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ExclusiveMinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\FormatConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\IfThenElseConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\ItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MaxItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MaxLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MaxPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MaximumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MinItemsConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MinLengthConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MinPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MinimumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\MultipleOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\NotConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\OneOfConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\PatternConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\PatternPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\PropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\PropertiesNamesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\RefConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\RequiredConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\TypeConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\UnevaluatedPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\UniqueItemsConstraint;

class Factory extends BaseFactory
{
    /**
     * @var array<string, class-string>
     */
    protected $constraintMap = [
        'schema' => Draft2019Constraint::class,
        'additionalProperties' => AdditionalPropertiesConstraint::class,
        'unevaluatedProperties' => UnevaluatedPropertiesConstraint::class,
        'additionalItems' => AdditionalItemsConstraint::class,
        'dependentSchemas' => DependentSchemasConstraint::class,
        'dependentRequired' => DependentRequiredConstraint::class,
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
