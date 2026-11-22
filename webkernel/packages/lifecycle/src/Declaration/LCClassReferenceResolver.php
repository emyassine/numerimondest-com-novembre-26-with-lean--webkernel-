<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Declaration;

use RuntimeException;

/** Resolves package-local class references against a package's PSR-4 namespace. */
final class LCClassReferenceResolver
{
    /**
     * Resolves a callback class, preserving an optional method suffix.
     * @param $reference string Fully qualified or package-local callback reference.
     * @param $psr4_prefixes list<string> PSR-4 namespace prefixes declared by the package.
     * @param $package_name string Package declaring the callback.
     * @return string Resolved class or class-and-method reference.
     * @throws RuntimeException If a relative reference has no unique package namespace.
     */
    public function resolve(string $reference, array $psr4_prefixes, string $package_name): string
    {
        [$class_name, $method] = array_pad(explode('::', $reference, 2), 2, null);

        if (str_starts_with($class_name, '\\') || class_exists($class_name)) {
            return $reference;
        }

        if (count($psr4_prefixes) !== 1) {
            throw new RuntimeException(sprintf(
                '[webkernel/lifecycle] Relative class reference "%s" in package "%s" requires exactly one PSR-4 namespace prefix; declare a fully qualified class name instead.',
                $reference,
                $package_name,
            ));
        }

        $resolved_class = $psr4_prefixes[0] . ltrim($class_name, '\\');

        return $method === null ? $resolved_class : $resolved_class . '::' . $method;
    }
}
