<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Declaration;

use Webkernel\Lifecycle\Install\LCPackageType;
use Webkernel\Utilities\JsonSchemaValidator;
use RuntimeException;

/** Validates lifecycle declarations before any declared work is dispatched. */
final class LCDeclarationValidator
{
    /**
     * Validates metadata and lifecycle declarations for one Composer package.
     * @param $package_name string Composer package name.
     * @param $package_type string Composer package type.
     * @param $webkernel_extra array<string, mixed> Webkernel metadata from composer.json.
     * @return void
     * @throws RuntimeException If a declaration is invalid.
     */
    public function validate(
        string $package_name,
        string $package_type,
        array $webkernel_extra,
    ): void {
        $schema_path = dirname(__DIR__, 2) . '/support/schemas/extra-webkernel.v1.json';
        $violations = (new JsonSchemaValidator())->validate(
            self::toJsonObject($webkernel_extra),
            $schema_path,
        );

        if ($violations !== []) {
            $violation = $violations[0];
            $property = $violation['property'] === ''
                ? 'extra.webkernel'
                : 'extra.webkernel.' . $violation['property'];
            $this->fail($package_name, sprintf('%s %s', $property, $violation['message']));
        }

        $module = $webkernel_extra['module'] ?? null;
        $type = LCPackageType::tryFrom($package_type);
        if ($type?->requiresParentModule() && (! is_string($module) || trim($module) === '')) {
            $this->fail($package_name, 'Feature package types require extra.webkernel.module.');
        }
    }

    /**
     * Converts Composer's associative extra values to JSON object/list values.
     * @param $webkernel_extra array<string, mixed> Webkernel metadata.
     * @return object JSON object with nested objects and lists preserved.
     */
    private static function toJsonObject(array $webkernel_extra): object
    {
        if (is_array($webkernel_extra['x-monorepo'] ?? null)) {
            $webkernel_extra['x-monorepo'] = (object) $webkernel_extra['x-monorepo'];
        }

        if (is_array($webkernel_extra['lifecycle'] ?? null)) {
            $lifecycle = $webkernel_extra['lifecycle'];
            if (is_array($lifecycle['events'] ?? null)) {
                $lifecycle['events'] = (object) $lifecycle['events'];
            }
            if (is_array($lifecycle['checks'] ?? null)) {
                foreach ($lifecycle['checks'] as $index => $check) {
                    if (! is_array($check)) {
                        continue;
                    }
                    if (is_array($check['on-fail'] ?? null)) {
                        $check['on-fail'] = (object) $check['on-fail'];
                    }
                    $lifecycle['checks'][$index] = (object) $check;
                }
            }
            $webkernel_extra['lifecycle'] = (object) $lifecycle;
        }

        return (object) self::toJsonValue($webkernel_extra);
    }

    /** Converts associative PHP arrays recursively while preserving JSON lists. */
    private static function toJsonValue(mixed $value): mixed
    {
        if (is_object($value)) {
            foreach (get_object_vars($value) as $key => $item) {
                $value->{$key} = self::toJsonValue($item);
            }

            return $value;
        }
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(self::toJsonValue(...), $value);
        }

        $object = [];
        foreach ($value as $key => $item) {
            $object[$key] = self::toJsonValue($item);
        }

        return (object) $object;
    }

    /** @throws RuntimeException Always throws the package-scoped declaration error. */
    private function fail(string $package_name, string $message): never
    {
        throw new RuntimeException(sprintf(
            '[webkernel/lifecycle] Invalid declaration in package "%s": %s',
            $package_name,
            $message,
        ));
    }
}
