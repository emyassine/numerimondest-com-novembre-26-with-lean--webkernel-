<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities;

use RuntimeException;
use Webkernel\Utilities\JsonSchema\Validator;

/** Validates JSON-compatible data against a JSON Schema document. */
final class JsonSchemaValidator
{
    /** @var array<string, object> */
    private static array $schema_cache = [];

    /**
     * @param $data mixed JSON-compatible value; objects must be represented as objects.
     * @param $schema_path string Path to a JSON Schema document.
     * @return list<array{property: string, message: string}> Validation errors.
     * @throws RuntimeException If the schema cannot be read or decoded.
     */
    public function validate(mixed $data, string $schema_path): array
    {
        $schema = self::loadSchema($schema_path);
        $validator = new Validator();
        $validator->validate($data, $schema);

        return array_map(
            static fn (array $error): array => [
                'property' => (string) ($error['property'] ?? ''),
                'message' => (string) ($error['message'] ?? 'JSON Schema validation failed.'),
            ],
            $validator->getErrors(),
        );
    }

    /** @throws RuntimeException If the schema file is unavailable or invalid JSON. */
    private static function loadSchema(string $schema_path): object
    {
        $real_path = realpath($schema_path);
        if ($real_path === false || ! is_file($real_path)) {
            throw new RuntimeException(sprintf('JSON Schema file not found: %s', $schema_path));
        }
        if (isset(self::$schema_cache[$real_path])) {
            return self::$schema_cache[$real_path];
        }

        $contents = file_get_contents($real_path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read JSON Schema file: %s', $real_path));
        }
        try {
            $schema = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid JSON Schema file: %s', $real_path), previous: $exception);
        }
        if (! is_object($schema)) {
            throw new RuntimeException(sprintf('JSON Schema root must be an object: %s', $real_path));
        }

        return self::$schema_cache[$real_path] = $schema;
    }
}
