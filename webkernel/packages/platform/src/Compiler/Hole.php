<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Compiler;

/** A typed, escaped per-request value in a compiled template. */
final readonly class Hole implements TemplateNode
{
    /** @param list<string> $path */
    public function __construct(
        public array $path,
        public Resolution $resolution = Resolution::PerRequest,
    ) {
        if ($path === [] || array_any($path, static fn (string $part): bool => preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $part) !== 1)) {
            throw new \InvalidArgumentException('Compiled template holes require a safe non-empty property path.');
        }
        if ($resolution !== Resolution::PerRequest) {
            throw new \InvalidArgumentException('Template holes currently support only per-request values.');
        }
        if ($path[0] === 'user' && (count($path) !== 2 || ! in_array($path[1], ['id', 'name', 'email'], true))) {
            throw new \InvalidArgumentException('Compiled user holes must select id, name, or email.');
        }
        if ($path[0] === 'csrfToken' && count($path) !== 1) {
            throw new \InvalidArgumentException('The compiled CSRF token is a scalar value.');
        }
    }
}
