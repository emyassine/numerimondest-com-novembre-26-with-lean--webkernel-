<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route;

use Psr\Http\Message\ServerRequestInterface;

/** Immutable snapshot of the current PHP HTTP request input. */
final readonly class Request
{
    /**
     * @param $query array<string, mixed>
     * @param $body array<string, mixed>
     * @param $files array<string, mixed>
     * @param $cookies array<string, mixed>
     * @param $headers array<string, string>
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query = [],
        private array $body = [],
        private array $files = [],
        private array $cookies = [],
        private array $headers = [],
        private string $content = '',
        private array $server = [],
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;

        $headers = self::extractHeaders($server);
        $is_json = str_contains(strtolower($headers['content-type'] ?? ''), 'application/json');
        $cached_body = $GLOBALS['WEBKERNEL_PARSED_REQUEST_BODY'] ?? null;
        $content = $is_json
            ? ($GLOBALS['WEBKERNEL_RAW_REQUEST_BODY'] ?? file_get_contents('php://input'))
            : '';
        unset($GLOBALS['WEBKERNEL_RAW_REQUEST_BODY'], $GLOBALS['WEBKERNEL_PARSED_REQUEST_BODY']);
        $content = is_string($content) ? $content : '';
        $body = self::arrayValue($_POST);
        if ($is_json && $content !== '') {
            $decoded = is_array($cached_body) ? $cached_body : json_decode($content, true);
            if (is_array($decoded)) { $body = $decoded; }
        }

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            (string) ($server['REQUEST_URI'] ?? '/'),
            self::arrayValue($_GET),
            $body,
            self::arrayValue($_FILES),
            self::arrayValue($_COOKIE),
            $headers,
            $content,
            $server,
        );
    }

    public static function fromPsr(ServerRequestInterface $request): self
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (is_array($values) && $values !== []) { $headers[strtolower($name)] = implode(', ', $values); }
        }
        parse_str($request->getUri()->getQuery(), $query);
        $content = (string) $request->getBody();
        $body = $request->getParsedBody();
        if (! is_array($body)) { $body = []; }
        if (str_contains(strtolower($headers['content-type'] ?? ''), 'application/json') && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) { $body = $decoded; }
        }
        $server = $request->getServerParams();
        if (! is_string($server['HTTP_HOST'] ?? null) || $server['HTTP_HOST'] === '') {
            $server['HTTP_HOST'] = $request->getUri()->getHost();
            if ($request->getUri()->getPort() !== null) {
                $server['HTTP_HOST'] .= ':'.$request->getUri()->getPort();
            }
        }

        return new self(
            strtoupper($request->getMethod()),
            $request->getUri()->getPath(),
            is_array($query) ? $query : [],
            $body,
            $request->getUploadedFiles(),
            $request->getCookieParams(),
            $headers,
            $content,
            $server,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function withPathAndMethod(string $path, string $method): self
    {
        $server = $this->server;
        $server['REQUEST_URI'] = $path;
        $server['REQUEST_METHOD'] = strtoupper($method);

        return new self(
            strtoupper($method),
            $path,
            $this->query,
            $this->body,
            $this->files,
            $this->cookies,
            $this->headers,
            $this->content,
            $server,
        );
    }

    /** Returns body input first, followed by query input. */
    public function input(string $key, mixed $default = null): mixed
    {
        return self::dotGet(array_replace($this->query, $this->body), $key, $default);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_replace($this->query, $this->body);
    }

    /** @return array<string, mixed> */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : self::dotGet($this->query, $key, $default);
    }

    /** @return array<string, mixed> */
    public function body(): array
    {
        return $this->body;
    }

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $this->files;
    }

    /** @return array<string, mixed> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    public function isJson(): bool
    {
        return str_contains(strtolower($this->header('content-type', '') ?? ''), 'application/json');
    }

    public function content(): string
    {
        return $this->content;
    }

    /** Current matched route metadata, or one route parameter / attribute. */
    public function route(?string $key = null, mixed $default = null): mixed
    {
        $route = Route::current();
        if ($route === null) { return null; }
        if ($key === null) { return new CurrentRoute($route); }

        return $route['parameters'][$key] ?? $route['attributes'][$key] ?? $default;
    }

    public function routeIs(string ...$patterns): bool
    {
        $name = Route::currentName();
        if ($name === null || $name === '') { return false; }
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name, FNM_NOESCAPE)) { return true; }
        }
        return false;
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        $path = is_string($path) ? trim($path, '/') : '';

        return $path === '' ? '/' : $path;
    }

    public function getHost(): string
    {
        $host = $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? null;
        if (! is_string($host) || $host === '') {
            $host = parse_url($this->uri, PHP_URL_HOST);
        }
        if (! is_string($host)) { return ''; }

        return preg_replace('/:\\d+\z/', '', $host) ?? $host;
    }

    public function getBaseUrl(): string
    {
        $script = $this->server['SCRIPT_NAME'] ?? '';
        if (! is_string($script) || $script === '' || $script === '/') { return ''; }
        $directory = str_replace('\\', '/', dirname($script));

        return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    }

    public function ip(): ?string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    public function userAgent(): ?string
    {
        $agent = $this->header('user-agent');

        return $agent === '' ? null : $agent;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** @return array<string, mixed> */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private static function dotGet(array $values, string $key, mixed $default): mixed
    {
        $value = $values;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) { return $default; }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @param $server array<string, mixed> @return array<string, string> */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $key => $name) {
            if (is_string($server[$key] ?? null)) {
                $headers[$name] = $server[$key];
            }
        }

        return $headers;
    }
}
