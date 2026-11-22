<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Traits\Conditionable;
use Webkernel\Utilities\Traits\Macroable;
use Webkernel\View\AppendableAttributeValue;
use Webkernel\View\Htmlable;

/** Canonical HTML attribute bag shared by Webkernel views and Filament components. */
class AttributeBag implements ArrayAccess, IteratorAggregate, JsonSerializable, Stringable, Htmlable
{
    use Conditionable, Macroable;

    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }

    public static function normalize(mixed $attributes): array
    {
        return match (true) {
            $attributes instanceof self => $attributes->getAttributes(),
            is_array($attributes) => $attributes,
            $attributes instanceof Traversable => iterator_to_array($attributes),
            default => throw new InvalidArgumentException('Component attribute spreads must be arrays or traversable values.'),
        };
    }

    public function all($keys = null): array
    {
        return $keys === null ? $this->attributes : Arr::only($this->attributes, Arr::wrap($keys));
    }

    public function first($default = null): mixed
    {
        return Arr::first($this->attributes, default: $default);
    }

    public function get($key, $default = null): mixed
    {
        return $this->attributes[$key] ?? value($default);
    }

    public function has($keys): bool
    {
        foreach (Arr::wrap($keys) as $key) {
            if (! array_key_exists($key, $this->attributes)) { return false; }
        }
        return true;
    }

    public function hasAny($keys): bool
    {
        foreach (Arr::wrap($keys) as $key) {
            if (array_key_exists($key, $this->attributes)) { return true; }
        }
        return false;
    }

    public function missing($key): bool { return ! $this->has($key); }

    public function only($keys): static
    {
        return new static($keys === null ? $this->attributes : Arr::only($this->attributes, Arr::wrap($keys)));
    }

    public function except($keys): static
    {
        return new static(Arr::except($this->attributes, Arr::wrap($keys)));
    }

    public function filter($callback): static
    {
        return new static(Arr::where($this->attributes, $callback));
    }

    public function whereStartsWith($needles): static
    {
        return $this->filter(fn ($value, $key) => array_any(Arr::wrap($needles), fn ($needle) => str_starts_with($key, $needle)));
    }

    public function whereDoesntStartWith($needles): static
    {
        return $this->filter(fn ($value, $key) => ! array_any(Arr::wrap($needles), fn ($needle) => str_starts_with($key, $needle)));
    }

    public function thatStartWith($needles): static { return $this->whereStartsWith($needles); }
    public function onlyProps($keys): static { return $this->only(static::extractPropNames(Arr::wrap($keys))); }
    public function exceptProps($keys): static { return $this->except(static::extractPropNames(Arr::wrap($keys))); }

    public function class($classList): static
    {
        return $this->merge(['class' => Arr::toCssClasses(Arr::wrap($classList))]);
    }

    public function style($styleList): static
    {
        return $this->merge(['style' => Arr::toCssStyles(Arr::wrap($styleList))]);
    }

    public function merge(array $attributeDefaults = [], $escape = true): static
    {
        $attributes = array_merge($attributeDefaults, $this->attributes);
        foreach ($attributeDefaults as $key => $default) {
            if ($key === 'class' || $key === 'style' || $default instanceof AppendableAttributeValue) {
                $left = $this->attributeString($default);
                $right = $this->attributeString($this->attributes[$key] ?? '');
                if ($key === 'style') {
                    $left = $left === '' ? '' : rtrim($left, '; ').';';
                    $right = $right === '' ? '' : rtrim($right, '; ').';';
                }
                $attributes[$key] = implode(' ', array_unique(array_filter([$left, $right], fn ($part) => $part !== '')));
            }
        }
        return new static($attributes);
    }

    protected function shouldEscapeAttributeValue($escape, $value): bool
    {
        return $escape && ! is_object($value) && $value !== null && ! is_bool($value);
    }

    public function prepends($value): AppendableAttributeValue { return new AppendableAttributeValue($value); }
    public function isEmpty(): bool { return $this->attributes === []; }
    public function isNotEmpty(): bool { return ! $this->isEmpty(); }
    public function getAttributes(): array { return $this->attributes; }

    public function setAttributes(array $attributes): void
    {
        if (($attributes['attributes'] ?? null) instanceof self) {
            $parent = $attributes['attributes'];
            unset($attributes['attributes']);
            $attributes = $parent->merge($attributes)->getAttributes();
        }
        $this->validate($attributes);
        $this->attributes = $attributes;
    }

    public static function extractPropNames(array $keys): array
    {
        $props = [];
        foreach ($keys as $key => $default) {
            $key = is_int($key) ? $default : $key;
            $props[] = $key;
            $props[] = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1-', $key));
        }
        return $props;
    }

    public function toArray(): array { return $this->attributes; }
    public function to_array(): array { return $this->attributes; }
    public function jsonSerialize(): mixed { return $this->attributes; }
    public function getIterator(): Traversable { return new ArrayIterator($this->attributes); }
    public function offsetExists(mixed $offset): bool { return array_key_exists($offset, $this->attributes); }
    public function offsetGet(mixed $offset): mixed { return $this->get($offset); }
    public function offsetSet(mixed $offset, mixed $value): void { $this->setAttributes(array_replace($this->attributes, [$offset => $value])); }
    public function offsetUnset(mixed $offset): void { unset($this->attributes[$offset]); }
    public function __invoke(array $attributeDefaults = []): static { return $this->merge($attributeDefaults); }
    public function toHtml(): string { return (string) $this; }

    public function __toString(): string
    {
        $html = [];
        foreach ($this->attributes as $key => $value) {
            if ($value === null || $value === false) { continue; }
            $name = str_replace('_', '-', (string) $key);
            $html[] = $value === true ? $name : $name.'="'.htmlspecialchars($this->attributeString($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false).'"';
        }
        return $html === [] ? '' : ' '.implode(' ', $html);
    }

    private function attributeString(mixed $value): string
    {
        if (is_array($value)) { return Arr::toCssClasses($value); }
        return $value === null || $value === false ? '' : (string) $value;
    }

    /** @param array<string, mixed> $attributes */
    private function validate(array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            if (! is_string($name) || preg_match('/\A[a-zA-Z_:][a-zA-Z0-9:._-]*\z/', $name) !== 1) {
                throw new InvalidArgumentException('Invalid HTML attribute name.');
            }
            $normalized_name = strtolower($name);
            if (str_starts_with($normalized_name, 'on') || $normalized_name === 'srcdoc') {
                throw new InvalidArgumentException('Executable HTML attributes are not allowed.');
            }
            if ($value === null || is_bool($value)) {
                continue;
            }
            if (in_array($normalized_name, ['class', 'style'], true) && is_array($value)) {
                continue;
            }
            if (! is_string($value) && ! is_int($value) && ! is_float($value) && ! $value instanceof Stringable) {
                throw new InvalidArgumentException('HTML attribute values must be scalar.');
            }
            if (in_array($normalized_name, ['href', 'src', 'action', 'formaction', 'poster', 'xlink:href'], true)) {
                $url = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $url = trim($url);
                $safe_inline_svg_avatar = $normalized_name === 'src' && self::isSafeInlineSvgAvatar($url);
                if (! $safe_inline_svg_avatar && (
                    preg_match('/[\x00-\x20]/', $url) === 1
                    || preg_match('/\A(?:https?:\/\/|mailto:|tel:|\/|[?#][^\x00-\x20]*|\.\.?\/)/i', $url) !== 1
                    || str_starts_with($url, '//')
                )) {
                    throw new InvalidArgumentException('Unsafe URL in HTML attributes.');
                }
            }
        }
    }

    /** Only the fixed, inert SVG shape used by Platform's local initials avatar may be embedded. */
    private static function isSafeInlineSvgAvatar(string $url): bool
    {
        $prefix = 'data:image/svg+xml;base64,';
        if (! str_starts_with($url, $prefix) || strlen($url) > 2048) {
            return false;
        }

        $svg = base64_decode(substr($url, strlen($prefix)), true);
        if (! is_string($svg)) {
            return false;
        }

        return preg_match(
            '~\A<svg xmlns="http://www\.w3\.org/2000/svg" viewBox="0 0 100 100" role="img"><rect width="100" height="100" rx="50" fill="#[a-fA-F0-9]{6}" /><text x="50" y="51" dominant-baseline="middle" text-anchor="middle" font-family="system-ui,sans-serif" font-size="38" font-weight="600" fill="#[fF]{3}">[\p{L}\p{N} ?]{1,8}</text></svg>\z~uD',
            $svg,
        ) === 1;
    }
}
