<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route;

/** Read-only route context exposed by the current Webkernel request. */
final readonly class CurrentRoute
{
    /** @param array{method: string, uri: string, name: string, action: mixed, parameters: array<string, mixed>, attributes: array<string, mixed>} $route */
    public function __construct(private array $route)
    {
    }

    public function getName(): ?string
    {
        $name = $this->route['name'];

        return $name === '' ? null : $name;
    }

    public function uri(): string
    {
        return $this->route['uri'];
    }

    public function action(): mixed
    {
        return $this->route['action'];
    }

    /** @return array<string, mixed> */
    public function parameters(): array
    {
        return $this->route['parameters'];
    }

    /** @param array<string, mixed> $parameters */
    public function withParameters(array $parameters): self
    {
        $route = $this->route;
        $route['parameters'] = $parameters;

        return new self($route);
    }

    /** @return array{method: string, uri: string, name: string, action: mixed, parameters: array<string, mixed>, attributes: array<string, mixed>} */
    public function toArray(): array
    {
        return $this->route;
    }

    public function parameter(string $name, mixed $default = null): mixed
    {
        return $this->route['parameters'][$name] ?? $default;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->route['attributes'];
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->route['attributes'][$name] ?? $default;
    }

    /** @return list<string> */
    public function middleware(): array
    {
        $middleware = $this->route['attributes'][Route::MIDDLEWARE] ?? [];
        if (is_string($middleware)) { $middleware = explode('|', $middleware); }

        return is_array($middleware)
            ? array_values(array_filter($middleware, 'is_string'))
            : [];
    }

    public function routeIs(string ...$patterns): bool
    {
        $name = $this->getName();
        if ($name === null) { return false; }
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name, FNM_NOESCAPE)) { return true; }
        }

        return false;
    }
}
