<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route;

use Webkernel\HttpFoundation\Request as HttpRequest;
use BackedEnum;
use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use Webkernel\Config\Config;

final class UrlGenerator
{
    public function route(string|BackedEnum $route, array $parameters = [], bool $absolute = true): string
    {
        $name = $route instanceof BackedEnum ? (string) $route->value : $route;
        $parameters = $this->normalizeParameters($parameters);
        $binding = null;
        foreach (Route::bindings() as $candidate) {
            if ($candidate->resolved_name() === $name) { $binding = $candidate; break; }
        }
        if ($binding === null) { return $this->absolute(Route::url($name, $parameters), $absolute); }
        preg_match_all('/\\{([^}?]+)(?:\\?)?\\}/', $binding->uri(), $matches);
        $pathParameters = array_intersect_key($parameters, array_fill_keys($matches[1], true));
        $queryParameters = array_diff_key($parameters, $pathParameters);
        $uri = Route::url($name, $pathParameters);
        if ($queryParameters !== []) { $uri .= '?'.http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986); }
        return $this->absolute($uri, $absolute);
    }

    public function signedRoute(string|BackedEnum $route, array $parameters = [], ?DateTimeInterface $expiration = null, bool $absolute = true): string
    {
        return $this->makeSignedRoute($route, $parameters, $expiration, $absolute);
    }

    public function temporarySignedRoute(string|BackedEnum $route, DateTimeInterface|DateInterval|int $expiration, array $parameters = [], bool $absolute = true): string
    {
        if ($expiration instanceof DateInterval) { $expiration = (new \DateTimeImmutable())->add($expiration); }
        elseif (is_int($expiration)) { $expiration = (new \DateTimeImmutable())->modify('+'.$expiration.' seconds'); }
        return $this->makeSignedRoute($route, $parameters, $expiration, $absolute);
    }

    public function to(string $path, array $extra = [], ?bool $secure = null): string
    {
        if (preg_match('~^https?://~i', $path) === 1) { return $path; }
        $base = rtrim($this->baseUrl($secure), '/');
        return $base.'/'.ltrim($path, '/');
    }

    public function asset(string $path, ?bool $secure = null): string
    {
        return $this->to($path, secure: $secure);
    }

    public function previous(mixed $fallback = false): string
    {
        $referer = Route::currentRequest()->headers->get('referer');
        return is_string($referer) && $referer !== '' ? $referer : (is_string($fallback) ? $fallback : '/');
    }

    public function getRequest(): Request
    {
        return Route::currentRequest();
    }

    public function hasValidSignature(HttpRequest $request, bool $absolute = true, array $ignoreQuery = []): bool
    {
        $query = $request->query->all();
        $signature = $query['signature'] ?? null;
        if (! is_string($signature) || $signature === '') { return false; }
        unset($query['signature']);
        foreach ($ignoreQuery as $key) { unset($query[$key]); }
        if (isset($query['expires']) && (int) $query['expires'] < time()) { return false; }
        $key = $this->key();
        if ($key === '') { return false; }
        $queryString = $this->buildQuery($query);
        $path = $request->getBaseUrl().$request->getPathInfo();
        $signed = ($absolute ? $request->getSchemeAndHttpHost() : '').$path.($queryString === '' ? '' : '?'.$queryString);
        return hash_equals(hash_hmac('sha256', $signed, $key), $signature);
    }

    public function hasValidRelativeSignature(HttpRequest $request, array $ignoreQuery = []): bool
    {
        return $this->hasValidSignature($request, false, $ignoreQuery);
    }

    private function makeSignedRoute(string|BackedEnum $route, array $parameters, ?DateTimeInterface $expiration, bool $absolute): string
    {
        if ($expiration !== null) { $parameters['expires'] = $expiration->getTimestamp(); }
        $parameters = $this->normalizeParameters($parameters);
        $url = $this->route($route, $parameters, $absolute);
        $parts = parse_url($url);
        if (! is_array($parts)) { throw new InvalidArgumentException('Unable to sign the generated route URL.'); }
        parse_str($parts['query'] ?? '', $query);
        $queryString = $this->buildQuery($query);
        $payload = ($absolute && isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : '').($parts['path'] ?? '/').($queryString === '' ? '' : '?'.$queryString);
        $query['signature'] = hash_hmac('sha256', $payload, $this->key());
        $signed = ($parts['scheme'] ?? '').(isset($parts['host']) ? '://'.$parts['host'] : '').(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '/').'?'.$this->buildQuery($query);
        return $signed;
    }

    private function normalizeParameters(array $parameters): array
    {
        foreach ($parameters as $key => $value) {
            if ($value instanceof BackedEnum) { $value = $value->value; }
            elseif (is_object($value) && method_exists($value, 'getRouteKey')) { $value = $value->getRouteKey(); }
            elseif (is_object($value) && method_exists($value, '__toString')) { $value = (string) $value; }
            if (! is_scalar($value) && $value !== null) { throw new InvalidArgumentException('Route parameters must be scalar, stringable, or backed enums.'); }
            $parameters[$key] = $value === null ? '' : (string) $value;
        }
        return $parameters;
    }

    private function absolute(string $path, bool $absolute): string
    {
        return $absolute ? $this->to($path) : $path;
    }

    private function baseUrl(?bool $secure = null): string
    {
        $configured = Config::get('app.url');
        if (is_string($configured) && $configured !== '') {
            if ($secure === true) { $configured = preg_replace('~^http:~i', 'https:', $configured); }
            elseif ($secure === false) { $configured = preg_replace('~^https:~i', 'http:', $configured); }
            return rtrim($configured, '/');
        }
        $request = Route::currentRequest();
        $scheme = $secure === true ? 'https' : ($secure === false ? 'http' : $request->getScheme());
        return $scheme.'://'.$request->getHttpHost().rtrim($request->getBaseUrl(), '/');
    }

    private function key(): string
    {
        $key = Config::get('app.key', '');
        if (! is_string($key)) { return ''; }
        return str_starts_with($key, 'base64:') ? (base64_decode(substr($key, 7), true) ?: '') : $key;
    }

    private function buildQuery(array $query): string
    {
        ksort($query);
        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
