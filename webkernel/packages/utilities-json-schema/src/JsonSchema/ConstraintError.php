<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema;

use Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException;

enum ConstraintError: string
{
    case AdditionalItems = 'additionalItems';
    case AdditionalProperties = 'additionalProp';
    case AllOf = 'allOf';
    case AnyOf = 'anyOf';
    case Dependencies = 'dependencies';
    case Disallow = 'disallow';
    case DivisibleBy = 'divisibleBy';
    case Enum = 'enum';
    case Constant = 'const';
    case Contains = 'contains';
    case ExclusiveMinimum = 'exclusiveMinimum';
    case ExclusiveMaximum = 'exclusiveMaximum';
    case False = 'false';
    case FormatColor = 'colorFormat';
    case FormatDate = 'dateFormat';
    case FormatDateTime = 'dateTimeFormat';
    case FormatDateUtc = 'dateUtcFormat';
    case FormatDuration = 'durationFormat';
    case FormatEmail = 'emailFormat';
    case FormatHostname = 'styleHostName';
    case FormatIp = 'ipFormat';
    case FormatJsonPointer = 'jsonPointerFormat';
    case FormatPhone = 'phoneFormat';
    case FormatRegex = 'regexFormat';
    case FormatStyle = 'styleFormat';
    case FormatTime = 'timeFormat';
    case FormatUriTemplate = 'uriTemplateFormat';
    case FormatUrl = 'urlFormat';
    case FormatUrlRef = 'urlRefFormat';
    case FormatUuid = 'uuidFormat';
    case InvalidSchema = 'invalidSchema';
    case LengthMax = 'maxLength';
    case LengthMin = 'minLength';
    case MaxContains = 'maxContains';
    case Maximum = 'maximum';
    case MinContains = 'minContains';
    case MinItems = 'minItems';
    case Minimum = 'minimum';
    case MissingError = 'missingError';
    case MissingMaximum = 'missingMaximum';
    case MissingMinimum = 'missingMinimum';
    case MaxItems = 'maxItems';
    case MultipleOf = 'multipleOf';
    case Not = 'not';
    case OneOf = 'oneOf';
    case Required = 'required';
    case Requires = 'requires';
    case Pattern = 'pattern';
    case PregexInvalid = 'pregrex';
    case PropertiesMin = 'minProperties';
    case PropertiesMax = 'maxProperties';
    case PropertyNames = 'propertyNames';
    case Type = 'type';
    case UniqueItems = 'uniqueItems';
    case UnevaluatedProperties = 'unevaluatedProperties';
    case ContentMediaType = 'contentMediaType';
    case ContentEncoding = 'contentEncoding';

    /**
     * @return string
     */
    public function getMessage()
    {
        $name = $this->value;
        static $messages = [
            self::AdditionalItems->value => 'The item %s[%s] is not defined and the definition does not allow additional items',
            self::AdditionalProperties->value => 'The property %s is not defined and the definition does not allow additional properties',
            self::AllOf->value => 'Failed to match all schemas',
            self::AnyOf->value => 'Failed to match at least one schema',
            self::Dependencies->value => '%s depends on %s, which is missing',
            self::Disallow->value => 'Disallowed value was matched',
            self::DivisibleBy->value => 'Is not divisible by %s',
            self::Enum->value => 'Does not have a value in the enumeration %s',
            self::Constant->value => 'Does not have a value equal to %s',
            self::Contains->value => 'Does not have a value valid to contains schema',
            self::ExclusiveMinimum->value => 'Must have a minimum value greater than %s',
            self::ExclusiveMaximum->value => 'Must have a maximum value less than %s',
            self::False->value => 'Boolean schema false',
            self::FormatColor->value => 'Invalid color',
            self::FormatDate->value => 'Invalid date %s, expected format YYYY-MM-DD',
            self::FormatDateTime->value => 'Invalid date-time %s, expected format YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DDThh:mm:ss+hh:mm',
            self::FormatDateUtc->value => 'Invalid time %s, expected integer of milliseconds since Epoch',
            self::FormatDuration->value => 'Invalid duration %s, expected ISO 8601 duration such as P1DT12H',
            self::FormatEmail->value => 'Invalid email',
            self::FormatHostname->value => 'Invalid hostname',
            self::FormatIp->value => 'Invalid IP address',
            self::FormatJsonPointer->value => 'Invalid JSON pointer',
            self::FormatPhone->value => 'Invalid phone number',
            self::FormatRegex->value => 'Invalid regex format %s',
            self::FormatStyle->value => 'Invalid style',
            self::FormatTime->value => 'Invalid time %s, expected format hh:mm:ss',
            self::FormatUriTemplate->value => 'Invalid URI template format',
            self::FormatUrl->value => 'Invalid URL format',
            self::FormatUrlRef->value => 'Invalid URL reference format',
            self::FormatUuid->value => 'Invalid UUID format',
            self::LengthMax->value => 'Must be at most %d characters long',
            self::InvalidSchema->value => 'Schema is not valid',
            self::LengthMin->value => 'Must be at least %d characters long',
            self::MaxContains->value => 'There must be a maximum of %d valid items in the array, %d found',
            self::MaxItems->value => 'There must be a maximum of %d items in the array, %d found',
            self::Maximum->value => 'Must have a maximum value less than or equal to %s',
            self::MinContains->value => 'There must be a minimum of %d valid items in the array, %d found',
            self::MinItems->value => 'There must be a minimum of %d items in the array, %d found',
            self::Minimum->value => 'Must have a minimum value greater than or equal to %s',
            self::MissingMaximum->value => 'Use of exclusiveMaximum requires presence of maximum',
            self::MissingMinimum->value => 'Use of exclusiveMinimum requires presence of minimum',
            /*self::MissingError->value => 'Used for tests; this error is deliberately commented out',*/
            self::MultipleOf->value => 'Must be a multiple of %s',
            self::Not->value => 'Matched a schema which it should not',
            self::OneOf->value => 'Failed to match exactly one schema',
            self::Required->value => 'The property %s is required',
            self::Requires->value => 'The presence of the property %s requires that %s also be present',
            self::Pattern->value => 'Does not match the regex pattern %s',
            self::PregexInvalid->value => 'The pattern %s is invalid',
            self::PropertiesMin->value => 'Must contain a minimum of %d properties',
            self::PropertiesMax->value => 'Must contain no more than %d properties',
            self::PropertyNames->value => 'Property name %s is invalid',
            self::Type->value => '%s value found, but %s is required',
            self::UniqueItems->value => 'There are no duplicates allowed in the array',
            self::UnevaluatedProperties->value => 'The property %s is not evaluated and the definition does not allow unevaluated properties',
            self::ContentMediaType->value => 'Value is not valid with content media type',
            self::ContentEncoding->value => 'Value is not valid with content encoding',
        ];

        if (!isset($messages[$name])) {
            throw new InvalidArgumentException('Missing error message for ' . $name);
        }

        return $messages[$name];
    }
}
