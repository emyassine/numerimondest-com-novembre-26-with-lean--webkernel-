<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests;

use Webkernel\Utilities\JsonSchema\Constraints\Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\DraftIdentifiers;
use Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException;
use Webkernel\Utilities\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidateWithAssocSchema(): void
    {
        $schema = json_decode('{"properties":{"propertyOne":{"type":"array","items":[{"type":"string"}]}}}', true);
        $data = json_decode('{"propertyOne":[42]}', true);

        $validator = new Validator();
        $validator->validate($data, $schema);

        $this->assertFalse($validator->isValid(), 'Validation succeeded, but should have failed.');
    }

    public function testValidateWithAssocSchemaWithRelativeRefs(): void
    {
        $schema = json_decode(file_get_contents(__DIR__ . '/fixtures/relative.json'), true);
        $data = json_decode('{"foo":{"foo": "bar"}}', false);

        $validator = new Validator();
        $validator->validate($data, $schema);

        $this->assertTrue($validator->isValid(), 'Validation failed, but should have succeeded.');
    }

    public function testBadAssocSchemaInput(): void
    {
        $schema = ['propertyOne' => fopen('php://stdout', 'wb')];
        $data = json_decode('{"propertyOne":[42]}', true);

        $validator = new Validator();

        $this->expectException(InvalidArgumentException::class);
        $validator->validate($data, $schema);
    }

    public function testDeprecatedCheckDelegatesToValidate(): void
    {
        $schema = json_decode('{"type":"string"}');
        $data = json_decode('42');

        $validator = new Validator();
        $validator->check($data, $schema);

        $this->assertFalse($validator->isValid(), 'Validation succeeded, but should have failed.');
    }

    public function testDeprecatedCoerceDelegatesToValidate(): void
    {
        $schema = json_decode('{"type":"integer"}');
        $data = json_decode('"42"');

        $validator = new Validator();
        $validator->coerce($data, $schema);

        $this->assertTrue($validator->isValid(), 'Validation failed, but should have succeeded.');
    }

    #[DataProvider('draftIdentifiersNotSupportedForStrictMode')]
    public function testItThrowsForStrictValidationOnNonSupportingDraft(DraftIdentifiers $draft): void
    {
        $data = json_decode('"42"', false);
        $schema = json_decode('{"type":"integer"}', false);
        $factory = new Factory(null, null, Constraint::CHECK_MODE_NORMAL | Constraint::CHECK_MODE_STRICT);
        $factory->setDefaultDialect($draft->value);
        $validator = new Validator($factory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown constraint ' . $draft->toConstraintName());

        $validator->validate($data, $schema);
    }

    public static function draftIdentifiersNotSupportedForStrictMode(): \Generator
    {
        foreach (DraftIdentifiers::cases() as $draft) {
            switch ($draft) {
                case DraftIdentifiers::Draft6:
                case DraftIdentifiers::Draft7:
                case DraftIdentifiers::Draft201909:
                    break;
                default:
                    yield $draft->toConstraintName() => [$draft];
            }
        }
    }
}
