<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use InvalidArgumentException;
use Stringable;

/** Immutable request data passed to a compiled panel template. */
final readonly class Frame
{
    /** @param array<string, mixed> $values */
    public function __construct(
        public UserIdentity|null $user,
        public int $abilities,
        public string $csrfToken,
        private array $values = [],
        public string $roleSignature = 'default',
    ) {
    }

    /** @param list<string> $path */
    public function value(array $path): mixed
    {
        if ($path === []) {
            throw new InvalidArgumentException('A compiled frame value path cannot be empty.');
        }

        $value = $this->values;
        foreach ($path as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }
            throw new InvalidArgumentException('Compiled frame value is unavailable: '.implode('.', $path));
        }

        return $value;
    }

    /** Escapes scalar and stringable values for an HTML text or quoted-attribute context. */
    public function e(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            $value = $value === true ? '1' : '';
        } elseif (! is_string($value) && ! is_int($value) && ! is_float($value) && ! $value instanceof Stringable) {
            throw new InvalidArgumentException('Compiled HTML holes accept only scalar or stringable values.');
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function allows(int $required_abilities): bool
    {
        return ($this->abilities & $required_abilities) === $required_abilities;
    }
}
