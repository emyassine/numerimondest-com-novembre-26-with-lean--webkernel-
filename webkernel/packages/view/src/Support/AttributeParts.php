<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Support;

use InvalidArgumentException;
use Webkernel\View\AttributeBag;

/** Builds validated attribute bags for a component's declared HTML parts. */
final class AttributeParts
{
    /** @var array<string, array<string, AttributeBag>> */
    private static array $empty_parts = [];

    /** @param list<string> $declared_parts @return array<string, AttributeBag> */
    public static function make(mixed $parts, array $declared_parts, string $owner = 'component'): array
    {
        if (! is_array($parts)) {
            throw new InvalidArgumentException(ucfirst($owner).' part attributes must be an array.');
        }

        $cache_key = implode("\0", $declared_parts);
        $bags = self::$empty_parts[$cache_key] ??= self::emptyParts($declared_parts);
        if (self::isPrepared($parts, $declared_parts)) {
            return $parts;
        }

        foreach ($parts as $name => $attributes) {
            if (! is_string($name) || ! array_key_exists($name, $bags)) {
                throw new InvalidArgumentException('Unknown '.$owner.' attribute part: '.(string) $name);
            }

            $bags[$name] = $attributes instanceof AttributeBag
                ? $attributes
                : new AttributeBag(AttributeBag::normalize($attributes));
        }

        return $bags;
    }

    /** @param list<string> $declared_parts @return array<string, AttributeBag> */
    private static function emptyParts(array $declared_parts): array
    {
        $parts = [];
        foreach ($declared_parts as $part) {
            $parts[$part] = new AttributeBag();
        }

        return $parts;
    }

    /** @param array<mixed> $parts @param list<string> $declared_parts */
    private static function isPrepared(array $parts, array $declared_parts): bool
    {
        if (count($parts) !== count($declared_parts)) {
            return false;
        }
        foreach ($declared_parts as $part) {
            if (! isset($parts[$part]) || ! $parts[$part] instanceof AttributeBag) {
                return false;
            }
        }

        return true;
    }
}
