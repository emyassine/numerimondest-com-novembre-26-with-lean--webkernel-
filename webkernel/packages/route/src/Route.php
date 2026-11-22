<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route;

use Webkernel\Utilities\Traits\Macroable;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionEnum;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Webkernel\Container\Container;
use Webkernel\HttpFoundation\Request as HttpRequest;
use Webkernel\Models\Model;
use Webkernel\Route\Action\MultipageAction;
use Webkernel\Route\Action\RedirectAction;
use Webkernel\Route\Action\ViewAction;
use Webkernel\Route\Compile\Cache;
use Webkernel\Route\Compile\Generator;
use Webkernel\Route\Dispatch\Dispatcher;
use Webkernel\Route\Dispatch\Matched;
use Webkernel\Route\Dispatch\MethodNotAllowed;
use Webkernel\Route\Dispatch\NotMatched;
use Webkernel\Route\Group\PendingGroup;
use Webkernel\Route\Uri\Uri;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;
use Webkernel\Utilities\Contracts\Responsable;

/**
 * Application router. FastRoute MarkBased engine, owned in this package.
 *
 * Registration is fluent: Route::view(...)->name('dashboard'). Panel access
 * policies run before handlers and route middleware wraps handler invocation.
 *
 * @phpstan-import-type Extra from Generator
 * @phpstan-import-type RouteData from Generator
 * @phpstan-import-type ParsedRoutes from Pattern
 * @phpstan-type NamedRoutes array<string, ParsedRoutes>
 */
final class Route
{
    use Macroable;

    public const NAME = '_name';
    public const PANEL = '_panel';
    public const CLUSTER = '_cluster';
    public const RESOURCE = '_resource';
    public const PAGE = '_page';
    public const PERMISSION = '_permission';
    public const PANEL_AUTHENTICATED = '_panel_authenticated';
    public const PANEL_LOGIN_URL = '_panel_login_url';
    public const REGEX = '_route';
    public const VIEW = '_view';
    public const DOMAIN = '_domain';
    public const MIDDLEWARE = '_middleware';

    private static bool $declared_loaded = false;
    private static bool $route_collection_dirty = false;
    private static ?string $deployment_generation = null;
    /** @var list<array{methods: list<string>, uri: string, name: string, action: string}>|null */
    private static ?array $deployed_list = null;
    /** @var list<callable(): void> */
    private static array $route_bootstrappers = [];

    /** @var list<string>|null */
    private static ?array $configured_route_files = null;
    /** @var list<string>|null */
    private static ?array $configured_route_dependencies = null;

    /** @var array<string, class-string> */
    private static array $middleware_aliases = [];

    /** @var array<string, list<string>> */
    private static array $middleware_groups = [];

    /** @var array<string, string> */
    private static array $patterns = [];

    /** @var array<string, Closure(mixed, string): mixed> */
    private static array $parameter_binders = [];

    /** @var array<string, array<string, ReflectionNamedType|null>> */
    private static array $handler_parameter_type_cache = [];

    /** @var array{method: string, uri: string, name: string, action: mixed, parameters: array<string, mixed>, attributes: array<string, mixed>}|null */
    private static ?array $current_route = null;

    private static ?Request $active_request = null;

    private static ?self $instance = null;

    private string $group_prefix = '';
    private string $group_name = '';
    private string $group_domain = '';

    /** @var list<string> */
    private array $group_middleware = [];

    /** @var list<string> */
    private array $group_without_middleware = [];

    /** @var array<string, string> */
    private array $group_wheres = [];

    /** @var list<Binding> */
    private array $bindings = [];

    private ?Uri $uris = null;

    public static function api_name(): string
    {
        return 'route';
    }

    public static function app(): self
    {
        $route = self::$instance ??= new self();
        self::prepare_deployment();
        $route->ensure_declared();

        return $route;
    }

    /** Reset process singleton (tests). */
    public static function flush(): void
    {
        self::$declared_loaded = false;
        self::$route_collection_dirty = false;
        self::$deployment_generation = null;
        self::$deployed_list = null;
        self::$route_bootstrappers = [];
        self::$configured_route_files = null;
        self::$current_route = null;
        self::$active_request = null;
        self::$parameter_binders = [];
        self::$handler_parameter_type_cache = [];
        self::$instance = null;
        Cache::reset();
    }

    public static function get(string $uri, mixed $action): Binding
    {
        return self::app()->add(['GET', 'HEAD'], $uri, $action);
    }

    public static function post(string $uri, mixed $action): Binding
    {
        return self::app()->add('POST', $uri, $action);
    }

    public static function put(string $uri, mixed $action): Binding
    {
        return self::app()->add('PUT', $uri, $action);
    }

    public static function patch(string $uri, mixed $action): Binding
    {
        return self::app()->add('PATCH', $uri, $action);
    }

    public static function delete(string $uri, mixed $action): Binding
    {
        return self::app()->add('DELETE', $uri, $action);
    }

    public static function options(string $uri, mixed $action): Binding
    {
        return self::app()->add('OPTIONS', $uri, $action);
    }

    public static function query(string $uri, mixed $action): Binding
    {
        return self::app()->add('QUERY', $uri, $action);
    }

    public static function any(string $uri, mixed $action): Binding
    {
        return self::app()->add('*', $uri, $action);
    }

    /** Set a default constraint for a named route parameter. */
    public static function pattern(string $parameter, string $expression): void
    {
        $router = self::app();
        self::$patterns[$parameter] = $expression;
        $router->invalidate();
    }

    /** Register a short route-middleware name. */
    public static function middlewareAlias(string $alias, string $middleware): void
    {
        self::$middleware_aliases[$alias] = $middleware;
    }

    /** Register a route parameter resolver. A null result makes the route not found. */
    public static function bindParameter(string $parameter, callable $resolver): void
    {
        if (preg_match('/\\A[a-zA-Z_][a-zA-Z0-9_]*\\z/', $parameter) !== 1) {
            throw new InvalidArgumentException('Route parameter names must be valid PHP identifiers.');
        }
        self::$parameter_binders[$parameter] = $resolver instanceof Closure
            ? $resolver
            : Closure::fromCallable($resolver);
    }

    /** Register an ordered middleware group such as `web`. */
    public static function middlewareGroup(string $name, array $middleware): void
    {
        self::$middleware_groups[$name] = array_values(array_map('strval', $middleware));
    }

    /** Append package middleware to a group without replacing application middleware. */
    public static function appendMiddlewareGroup(string $name, array $middleware): void
    {
        self::$middleware_groups[$name] = array_values(array_unique([
            ...(self::$middleware_groups[$name] ?? []),
            ...array_map('strval', $middleware),
        ]));
    }

    /**
     * @param list<string> $methods
     */
    public static function match(array $methods, string $uri, mixed $action): Binding
    {
        return self::app()->add(array_map(strtoupper(...), $methods), $uri, $action);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function view(string $uri, string $view, array $data = [], int $status = 200): Binding
    {
        return self::get($uri, new ViewAction($view, $data, $status))->as_view($view);
    }

    /** Register a server-rendered subpage route beneath one view URL. */
    public static function multipage(string $prefix, string $view, array $data = []): void
    {
        $prefix = '/'.trim($prefix, '/');
        if ($prefix === '/') {
            throw new InvalidArgumentException('Multipage routes require a non-root prefix.');
        }
        $base = self::app()->join_uri(self::app()->group_prefix, $prefix);
        $name_prefix = str_replace('/', '.', trim($prefix, '/')).'.';
        self::view($prefix, $view, array_merge($data, [
            'multipage_current' => '',
            'multipage_base' => $base,
        ]))->name($name_prefix.'index');
        self::get($prefix.'/{multipage_current}', new MultipageAction($view, $base, $data))
            ->where('multipage_current', '[a-z0-9]+(?:[.-][a-z0-9]+)*(?:/[a-z0-9]+(?:[.-][a-z0-9]+)*)*')
            ->as_view($view)
            ->name($name_prefix.'subpage');
    }

    public static function redirect(string $uri, string $destination, int $status = 302): Binding
    {
        return self::get($uri, new RedirectAction($destination, $status));
    }

    public static function permanent_redirect(string $uri, string $destination): Binding
    {
        return self::redirect($uri, $destination, 301);
    }

    public static function fallback(mixed $action): Binding
    {
        return self::any('/{path:.*}', $action);
    }

    public static function prefix(string $prefix): PendingGroup
    {
        return (new PendingGroup(self::app()))->prefix($prefix);
    }

    public static function name(string $name): PendingGroup
    {
        return (new PendingGroup(self::app()))->name($name);
    }

    /**
     * @param  string|list<string>  $middleware
     */
    public static function middleware(string|array $middleware): PendingGroup
    {
        return (new PendingGroup(self::app()))->middleware($middleware);
    }

    public static function withoutMiddleware(string|array $middleware): PendingGroup
    {
        return (new PendingGroup(self::app()))->withoutMiddleware($middleware);
    }

    public static function domain(?string $domain): PendingGroup
    {
        return (new PendingGroup(self::app()))->domain($domain ?? '');
    }

    public static function has(string $name): bool
    {
        foreach (self::list() as $route) {
            if ($route['name'] === $name) { return true; }
        }

        return false;
    }

    /** @return list<Binding> All declared and programmatically registered routes. */
    public static function bindings(): array
    {
        $router = self::app();

        return $router->bindings;
    }

    /**
     * @param array{prefix?: string, as?: string, name?: string, domain?: string, middleware?: string|list<string>, where?: array<string, string>}|callable $attributes
     */
    public static function group(array|callable $attributes, ?callable $routes = null): void
    {
        if (is_callable($attributes)) {
            $attributes();

            return;
        }
        if ($routes === null) {
            throw new \InvalidArgumentException('Route group requires a callback.');
        }
        PendingGroup::from_attributes(self::app(), $attributes)->group($routes);
    }

    /**
     * @param array<string, string> $parameters
     */
    public static function url(string $name, array $parameters = []): string
    {
        return self::app()->uri_generator()->for_name($name, $parameters);
    }

    /** The route currently being dispatched, including package-defined attributes. */
    public static function current(): ?array
    {
        return self::$current_route;
    }

    public static function currentName(): ?string
    {
        return self::$current_route['name'] ?? null;
    }

    /** Current request snapshot for helpers and request-aware package code. */
    public static function currentRequest(): Request
    {
        return self::$active_request ?? Request::capture();
    }

    /** Match an already-captured request without dispatching its handler. */
    public static function matchRequest(Request $request): ?CurrentRoute
    {
        $uri = parse_url($request->uri(), PHP_URL_PATH);
        $uri = is_string($uri) ? rawurldecode($uri) : '/';
        if ($uri !== '/' && str_ends_with($uri, '/')) { $uri = rtrim($uri, '/') ?: '/'; }
        $host = self::normalize_host($request->getHost());
        $previous_request = self::$active_request;
        $previous_route = self::$current_route;
        try {
            $data = self::dispatch_data($host);
        } finally {
            self::$active_request = $previous_request;
            self::$current_route = $previous_route;
        }
        $result = self::match_static($data, $request->method(), $uri)
            ?? (new Dispatcher($data))->dispatch($request->method(), $uri);
        if (! $result instanceof Matched) { return null; }

        $parameters = $result->variables;
        $domain = $result->extra[self::DOMAIN] ?? null;
        if (is_string($domain) && $domain !== '' && str_contains($domain, '{')) {
            $parameters = Binding::domain_variables($result->extra, $host) + $parameters;
        }

        return new CurrentRoute([
            'method' => strtoupper($request->method()),
            'uri' => $uri,
            'name' => is_string($result->extra[self::NAME] ?? null) ? $result->extra[self::NAME] : '',
            'action' => $result->handler,
            'parameters' => $parameters,
            'attributes' => $result->extra,
        ]);
    }

    /** Expand middleware declarations for a matched Webkernel request. */
    public static function middlewareForRequest(Request $request): array
    {
        $route = self::matchRequest($request);

        return $route === null ? [] : self::expand_middleware($route->middleware());
    }

    /** Expand middleware groups for a route matched by Webkernel. */
    public static function expandMiddleware(array $middleware): array
    {
        return self::expand_middleware($middleware);
    }

    /** Replay selected middleware for a matched request without invoking its handler. */
    public static function dispatchMiddlewareForRequest(Request $request, array $middleware, ?CurrentRoute $matched = null, bool $resolveParameters = true): ?CurrentRoute
    {
        $route = $matched ?? self::matchRequest($request);
        if ($route === null) { return null; }
        if (! self::authorize_route($route->attributes())) { return null; }

        $previous_request = self::$active_request;
        $previous_route = self::$current_route;
        self::$active_request = $request;
        self::$current_route = $route->toArray();
        try {
            $resolved_route = null;
            $next = static function () use ($route, &$resolved_route, $resolveParameters): mixed {
                if (! $resolveParameters) {
                    $resolved_route = $route;

                    return null;
                }
                $parameters = self::resolve_route_parameters($route->action(), $route->parameters());
                if ($parameters === null) {
                    http_response_code(404);
                    return null;
                }
                self::$current_route['parameters'] = $parameters;
                $resolved_route = $route->withParameters($parameters);

                return null;
            };
            foreach (array_reverse(self::expand_middleware($middleware)) as $declaration) {
                $next = self::wrap_middleware($declaration, $request, $next);
            }
            $next();

            return $resolved_route;
        } finally {
            self::$active_request = $previous_request;
            self::$current_route = $previous_route;
        }
    }

    /** Resolve named route values for a secondary handler such as a component mount method. */
    public static function resolveParametersFor(mixed $handler, array $parameters): ?array
    {
        $resolved = self::resolve_route_parameters($handler, $parameters);
        if ($resolved !== null && self::$current_route !== null) {
            self::$current_route['parameters'] = array_replace(self::$current_route['parameters'], $resolved);
        }

        return $resolved;
    }

    /**
     * Build FastRoute data from declared route files. Cold path only.
     *
     * @return RouteData
     */
    public static function compile_for_cache(string $host = ''): array
    {
        $route = self::app();

        return $route->build_data($host);
    }

    /**
     * Compiles one default route table and one table for every declared domain pattern.
     *
     * @return array{default: RouteData, domains: array<string, RouteData>, runtime: array<string, mixed>}
     */
    public static function compile_for_deployment(): array
    {
        $route = self::app();
        $domains = [];
        foreach ($route->bindings as $binding) {
            $pattern = $binding->domain_pattern();
            if ($pattern === '' || isset($domains[$pattern])) {
                continue;
            }
            $sample_host = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/',
                static fn (array $match): string => 'wkdeployment',
                $pattern,
            );
            if (! is_string($sample_host) || ! $binding->matches_host($sample_host)) {
                throw new \RuntimeException(sprintf('Unable to compile a representative host for route domain "%s".', $pattern));
            }
            $domains[$pattern] = $route->build_data($sample_host);
        }

        return [
            'default' => $route->build_data(''),
            'domains' => $domains,
            'runtime' => [
                'patterns' => self::$patterns,
                'middleware_aliases' => self::$middleware_aliases,
                'middleware_groups' => self::$middleware_groups,
                'parameter_binders' => self::$parameter_binders,
                'named_routes' => $route->named_routes(),
                'listed_routes' => $route->listed(),
                'runtime_bootstrappers' => require base_path('runtime/cache/declarations/webkernel_runtime_bootstrappers.php'),
            ],
        ];
    }

    /** Loads compiled route runtime metadata without requiring route source files. */
    private static function prepare_deployment(): void
    {
        $deployment = DeploymentLock::read(base_path());
        if ($deployment === null) {
            if (self::$deployment_generation !== null) {
                self::$declared_loaded = false;
                self::$route_collection_dirty = false;
                self::$deployed_list = null;
                self::$middleware_aliases = [];
                self::$middleware_groups = [];
                self::$patterns = [];
                self::$parameter_binders = [];
                self::$route_bootstrappers = [];
                self::$instance = null;
                self::$deployment_generation = null;
                Cache::reset();
            }

            return;
        }
        if (self::$deployment_generation === $deployment['generation'] && self::$instance?->uris !== null) {
            return;
        }
        Cache::reset();
        $payload = Cache::payload();
        $runtime = $payload['runtime'] ?? null;
        if (! is_array($runtime)) {
            throw new \RuntimeException('The deployed route runtime metadata is missing. Run ./platform deploy.');
        }
        foreach (['patterns', 'middleware_aliases', 'middleware_groups', 'parameter_binders', 'named_routes', 'listed_routes', 'runtime_bootstrappers'] as $key) {
            if (! is_array($runtime[$key] ?? null)) {
                throw new \RuntimeException(sprintf('The deployed route runtime metadata "%s" is invalid.', $key));
            }
        }
        self::$patterns = $runtime['patterns'];
        self::$middleware_aliases = $runtime['middleware_aliases'];
        self::$middleware_groups = $runtime['middleware_groups'];
        self::$parameter_binders = $runtime['parameter_binders'];
        self::$declared_loaded = true;
        self::$instance ??= new self();
        self::$instance->uris = new Uri($runtime['named_routes']);
        self::$deployed_list = $runtime['listed_routes'];
        self::$route_bootstrappers = [];
        self::$deployment_generation = $deployment['generation'];
        foreach ($runtime['runtime_bootstrappers'] as $bootstrapper) {
            if (! is_callable($bootstrapper)) {
                throw new \RuntimeException('The deployed route bootstrapper is invalid.');
            }
            $bootstrapper();
        }
    }

    public static function dispatch(
        ServerRequestInterface|string|null $method = null,
    	?string $uri = null,
    	?string $host = null
    ): mixed
    {
        if ($method instanceof ServerRequestInterface) {
            $request = $method;
            $previous_request = self::$active_request;
            self::$active_request = Request::fromPsr($request);
            try {
                return self::dispatch($request->getMethod(), $request->getUri()->getPath(), $request->getUri()->getHost());
            } finally {
                self::$active_request = $previous_request;
            }
        }
        if (self::$active_request === null) {
            self::$active_request = Request::capture();
            try {
                return self::dispatch($method, $uri, $host);
            } finally {
                self::$active_request = null;
            }
        }
        $method ??= $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri ??= $_SERVER['REQUEST_URI'] ?? '/';
        if (false !== $q = strpos($uri, '?')) {
            $uri = substr($uri, 0, $q);
        }
        $uri = rawurldecode($uri);
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/') ?: '/';
        }
        $host = self::normalize_host($host ?? (string) ($_SERVER['HTTP_HOST'] ?? ''));

        $data = self::dispatch_data($host);
        $result = self::match_static($data, $method, $uri)
            ?? (new Dispatcher($data))->dispatch($method, $uri);

        if ($result instanceof NotMatched) {
            http_response_code(404);

            return '';
        }
        if ($result instanceof MethodNotAllowed) {
            http_response_code(405);
            header('Allow: '.implode(', ', $result->allowed));

            return '';
        }

        $vars = $result->variables;
        $previous_route = self::$current_route;
        self::$current_route = [
            'method' => strtoupper((string) $method),
            'uri' => $uri,
            'name' => is_string($result->extra[self::NAME] ?? null) ? $result->extra[self::NAME] : '',
            'action' => $result->handler,
            'parameters' => $vars,
            'attributes' => $result->extra,
        ];
        try {
        if (! self::authorize_route($result->extra)) { return ''; }
        $domain = $result->extra[self::DOMAIN] ?? null;
        if (is_string($domain) && $domain !== '' && str_contains($domain, '{')) {
            $vars = Binding::domain_variables($result->extra, $host) + $vars;
            self::$current_route['parameters'] = $vars;
        }
        $out = self::invoke_with_middleware($result->handler, $vars, $result->extra[self::MIDDLEWARE] ?? '');
        if ($out instanceof Responsable) {
            $out = $out->toResponse(HttpRequest::capture());
        }
        if ($out instanceof SymfonyResponse) {
            return $out;
        }
        if ($out instanceof \Stringable) {
            return (string) $out;
        }

        return $out;
        } finally {
            self::$current_route = $previous_route;
        }
    }

    public static function run(): void
    {
        $response = self::dispatch();

        if ($response instanceof SymfonyResponse) {
            $response->send();

            return;
        }

        echo $response;
    }

    /**
     * Registered routes, methods merged per URI (GET|HEAD, …).
     *
     * @return list<array{methods: list<string>, uri: string, name: string, action: string}>
     */
    public static function list(): array
    {
        $route = self::app();

        return self::$deployment_generation !== null && self::$deployed_list !== null
            ? self::$deployed_list
            : $route->listed();
    }

    public function invalidate(): void
    {
        // Deployed routes are immutable. Runtime registration still happens when
        // a package boots (Liveview registers its endpoints from compiled data),
        // and must not discard the deployed URL generator.
        if (self::$deployment_generation !== null) {
            return;
        }
        $this->uris = null;
    }

    /** @param $routes */
    public function push_group(PendingGroup $group, callable $routes): void
    {
        $previous_prefix = $this->group_prefix;
        $previous_name = $this->group_name;
        $previous_domain = $this->group_domain;
        $previous_middleware = $this->group_middleware;
        $previous_without_middleware = $this->group_without_middleware;
        $previous_wheres = $this->group_wheres;

        $this->group_prefix = $group->prefix_value() === ''
            ? $previous_prefix
            : $this->join_uri($previous_prefix, $group->prefix_value());
        $this->group_name = $previous_name.$group->name_value();
        $this->group_domain = $group->domain_value() !== '' ? $group->domain_value() : $previous_domain;
        $this->group_middleware = array_values(array_unique(array_merge($previous_middleware, $group->middleware_value())));
        $this->group_without_middleware = array_values(array_unique(array_merge($previous_without_middleware, $group->without_middleware_value())));
        $this->group_wheres = $group->wheres_value() + $previous_wheres;
        $routes();
        $this->group_prefix = $previous_prefix;
        $this->group_name = $previous_name;
        $this->group_domain = $previous_domain;
        $this->group_middleware = $previous_middleware;
        $this->group_without_middleware = $previous_without_middleware;
        $this->group_wheres = $previous_wheres;
    }

    /**
     * @param string|list<string> $http_method
     */
    private function add(string|array $http_method, string $route, mixed $handler): Binding
    {
        $methods = array_values(array_map(strtoupper(...), (array) $http_method));
        $binding = new Binding(
            $this,
            $methods,
            $this->join_uri($this->group_prefix, $route),
            $handler,
            $this->group_name,
            $this->group_domain,
            array_values(array_diff($this->group_middleware, $this->group_without_middleware)),
            $this->group_wheres + self::$patterns,
            without_middleware: $this->group_without_middleware,
        );
        $this->bindings[] = $binding;
        self::$route_collection_dirty = true;
        $this->invalidate();

        return $binding;
    }

    /**
     * @param RouteData $data
     */
    private static function match_static(array $data, string $method, string $uri): ?Matched
    {
        $static = $data[0] ?? [];
        foreach ([$method, $method === 'HEAD' ? 'GET' : null, '*'] as $try) {
            if (! is_string($try)) {
                continue;
            }
            $row = $static[$try][$uri] ?? null;
            if (! is_array($row) || ! array_key_exists(0, $row)) {
                continue;
            }
            $extra = $row[1] ?? [];

            return new Matched($row[0], [], is_array($extra) ? $extra : []);
        }

        return null;
    }

    /**
     * @return RouteData
     */
    private static function dispatch_data(string $host): array
    {
        self::prepare_deployment();
        $cached = Cache::fresh_data($host);
        if (DeploymentLock::read(base_path()) !== null) {
            if ($cached === null) {
                throw new \RuntimeException('The deployed route table is missing. Run ./platform deploy.');
            }

            return $cached;
        }
        if (self::$route_collection_dirty && self::$instance !== null && self::$instance->bindings !== []) {
                return self::$instance->build_and_cache($host);
        }
        if ($cached !== null) {
            return $cached;
        }
        $active_request = self::$active_request;
        $current_route = self::$current_route;
        $configured_route_files = self::$configured_route_files;
        self::flush();
        self::$configured_route_files = $configured_route_files;
        self::$active_request = $active_request;
        self::$current_route = $current_route;
        return self::app()->build_and_cache($host);
    }

    /**
     * @return RouteData
     */
    private function compiled_data(string $host): array
    {
        if ($this->bindings === []) {
            $cached = Cache::fresh_data();
            if ($cached !== null) {
                return $cached;
            }
            $this->ensure_declared();
        }

        return $this->build_and_cache($host);
    }

    /**
     * @return RouteData
     */
    private function build_and_cache(string $host): array
    {
        $data = $this->build_data($host);
        Cache::write(Cache::path(), $data, [
            'compiled_at' => \time(),
            'host' => $host,
            'files' => Cache::fingerprints(),
        ]);
        self::$route_collection_dirty = false;

        return $data;
    }

    /**
     * @return RouteData
     */
    private function build_data(string $host): array
    {
        $specific = [];
        $general = [];
        foreach ($this->bindings as $binding) {
            $binding->apply_patterns(self::$patterns);
            if ($binding->domain_pattern() !== '') {
                if ($binding->matches_host($host)) {
                    $specific[] = $binding;
                }
                continue;
            }
            $general[] = $binding;
        }

        $generator = new Generator();
        $occupied = [];
        foreach ($specific as $binding) {
            $occupied[$binding->uri()] = true;
            $binding->compile($generator);
        }
        foreach ($general as $binding) {
            if (isset($occupied[$binding->uri()])) {
                continue;
            }
            $binding->compile($generator);
        }

        return $generator->get_data();
    }

    private function uri_generator(): Uri
    {
        if ($this->uris !== null) {
            return $this->uris;
        }
        return $this->uris = new Uri($this->named_routes());
    }

    /** @return NamedRoutes */
    private function named_routes(): array
    {
        $named = [];
        $registered = [];
        foreach ($this->bindings as $binding) {
            $name = $binding->resolved_name();
            if ($name !== '' && isset($registered[$name])) {
                $previous = $registered[$name];
                if (
                    $previous['methods'] === $binding->methods()
                    && $previous['uri'] === $binding->uri()
                    && $previous['action'] === $binding->action_label()
                    && $previous['extra'] === $binding->extra()
                ) {
                    continue;
                }
            }
            $binding->register_named($named);
            if ($name !== '') {
                $registered[$name] = [
                    'methods' => $binding->methods(),
                    'uri' => $binding->uri(),
                    'action' => $binding->action_label(),
                    'extra' => $binding->extra(),
                ];
            }
        }

        return $named;
    }

    /**
     * @param array<string, string> $vars
     */
    /** @param array<string, mixed> $attributes */
    private static function authorize_route(array $attributes): bool
    {
        $panel = $attributes[self::PANEL] ?? null;
        if ($panel === null) { return true; }

        $authenticated = $attributes[self::PANEL_AUTHENTICATED] ?? true;
        $login_url = $attributes[self::PANEL_LOGIN_URL] ?? null;
        $permission = $attributes[self::PERMISSION] ?? null;
        if (! is_string($panel) || $panel === '' || ! is_bool($authenticated) || ($authenticated && (! is_string($permission) || $permission === ''))) {
            http_response_code(403);
            return false;
        }
        if ($authenticated && \Webkernel\Accounts\Auth::user() === null) {
            if (! is_string($login_url) || $login_url === '') { http_response_code(403); return false; }
            header('Location: '.$login_url, true, 303);
            return false;
        }
        if ($authenticated && ! \Webkernel\Accounts\Auth::can($permission)) {
            http_response_code(403);
            return false;
        }

        return true;
    }

    private static function invoke(mixed $handler, array $vars): mixed
    {
        if (is_string($handler) && ! is_callable($handler) && class_exists($handler)) {
            return Container::getInstance()->call([$handler, '__invoke'], $vars);
        }
        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            return Container::getInstance()->call($handler, $vars);
        }
        if (is_callable($handler)) {
            return Container::getInstance()->call($handler, $vars);
        }

        throw new \RuntimeException('Invalid route action.');
    }

    private static function wrap_middleware(string $declaration, Request $request, callable $next): callable
    {
        [$name, $arguments] = array_pad(explode(':', $declaration, 2), 2, null);
        $parameters = $arguments === null ? [] : explode(',', $arguments);
        $class = self::$middleware_aliases[$name] ?? $name;
        if ($class === '' || ! class_exists($class)) {
            throw new \RuntimeException('Route middleware class is unavailable: '.$class);
        }
        $instance = Container::getInstance()->make($class);
        if (! $instance instanceof \Webkernel\Route\Middleware\RouteMiddleware) {
            throw new \RuntimeException('Route middleware must implement the Webkernel middleware contract: '.$class);
        }
        if ($parameters !== [] && ! $instance instanceof \Webkernel\Route\Middleware\ParameterizedRouteMiddleware) {
            throw new \RuntimeException('Route middleware does not accept declaration parameters: '.$class);
        }
        return static fn (): mixed => $instance->handle($request, $next, ...$parameters);
    }

    private static function invoke_with_middleware(mixed $handler, array $vars, mixed $middleware): mixed
    {
        $resolved = false;
        $resolved_vars = null;
        $next = static function () use ($handler, $vars, &$resolved, &$resolved_vars): mixed {
            if (! $resolved) {
                $resolved_vars = self::resolveParametersFor($handler, $vars);
                $resolved = true;
                if ($resolved_vars === null) {
                    http_response_code(404);
                    return '';
                }
                if (self::$current_route !== null) {
                    self::$current_route['parameters'] = $resolved_vars;
                }
            }

            return self::invoke($handler, $resolved_vars ?? $vars);
        };
        $classes = is_string($middleware) && $middleware !== '' ? explode('|', $middleware) : [];
        $classes = self::expand_middleware($classes);
        if ($classes === []) { return $next(); }
        $request = self::$active_request ?? Request::capture();
        foreach (array_reverse($classes) as $declaration) {
            $next = self::wrap_middleware($declaration, $request, $next);
        }

        return $next();
    }

    /** @param array<string, mixed> $parameters @return array<string, mixed>|null */
    private static function resolve_route_parameters(mixed $handler, array $parameters): ?array
    {
        $types = self::handler_parameter_types($handler);
        foreach ($parameters as $name => $value) {
            if (isset(self::$parameter_binders[$name]) && (is_scalar($value) || $value === null)) {
                $value = (self::$parameter_binders[$name])($value, $name);
                if ($value === null) { return null; }
                $parameters[$name] = $value;
                continue;
            }

            $type = $types[$name] ?? null;
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) { continue; }
            $class = $type->getName();
            if (is_subclass_of($class, BackedEnum::class)) {
                if ($value instanceof $class) { continue; }
                $backing_type = (new ReflectionEnum($class))->getBackingType()?->getName();
                $enum_value = $backing_type === 'int' && is_numeric($value) ? (int) $value : (string) $value;
                $value = $class::tryFrom($enum_value);
                if ($value === null) { return null; }
                $parameters[$name] = $value;
                continue;
            }
            if (is_subclass_of($class, Model::class)) {
                if ($value instanceof $class) { continue; }
                $value = $class::resolveRouteBinding((string) $value);
                if ($value === null) { return null; }
                $parameters[$name] = $value;
            }
        }

        return $parameters;
    }

    /** @return array<string, ReflectionNamedType|null> */
    private static function handler_parameter_types(mixed $handler): array
    {
        $cache_key = match (true) {
            $handler instanceof Closure => 'closure:'.spl_object_id($handler),
            is_array($handler) && count($handler) === 2 => 'method:'.(is_object($handler[0]) ? $handler[0]::class : $handler[0]).'::'.$handler[1],
            is_string($handler) => 'string:'.$handler,
            is_object($handler) => 'object:'.$handler::class,
            default => '',
        };
        if ($cache_key !== '' && isset(self::$handler_parameter_type_cache[$cache_key])) {
            return self::$handler_parameter_type_cache[$cache_key];
        }

        try {
            $reflection = match (true) {
                $handler instanceof Closure => new ReflectionFunction($handler),
                is_array($handler) && count($handler) === 2 => new ReflectionMethod($handler[0], $handler[1]),
                is_string($handler) && class_exists($handler) => new ReflectionMethod($handler, '__invoke'),
                is_string($handler) && function_exists($handler) => new ReflectionFunction($handler),
                is_object($handler) && is_callable($handler) => new ReflectionMethod($handler, '__invoke'),
                default => null,
            };
        } catch (\ReflectionException) {
            return [];
        }
        if ($reflection === null) { return []; }

        $types = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $types[$parameter->getName()] = $type instanceof ReflectionNamedType ? $type : null;
        }

        if ($cache_key !== '') { self::$handler_parameter_type_cache[$cache_key] = $types; }

        return $types;
    }

    /** @param list<string> $middleware @return list<string> */
    private static function expand_middleware(array $middleware, array $ancestors = []): array
    {
        $expanded = [];
        foreach ($middleware as $name) {
            if (isset(self::$middleware_groups[$name])) {
                if (isset($ancestors[$name])) { throw new \RuntimeException('Circular route middleware group: '.$name); }
                $nested = self::expand_middleware(self::$middleware_groups[$name], $ancestors + [$name => true]);
                array_push($expanded, ...$nested);
                continue;
            }
            $expanded[] = $name;
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return list<array{methods: list<string>, uri: string, name: string, action: string}>
     */
    private function listed(): array
    {
        $rows = [];
        foreach ($this->bindings as $binding) {
            $methods = $binding->methods();
            $order = [
            	'GET' => 0,
            	'HEAD' => 1,
            	'QUERY' => 2,
            	'POST' => 3,
            	'PUT' => 4,
            	'PATCH' => 5,
            	'DELETE' => 6,
            	'OPTIONS' => 7
            ];
            usort($methods, static fn (string $a, string $b): int => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
            $rows[] = [
                'methods' => $methods,
                'uri' => $binding->uri(),
                'name' => $binding->resolved_name(),
                'action' => $binding->action_label(),
            ];
        }
        usort($rows, static function (array $a, array $b): int {
            return [$a['uri'], $a['name']] <=> [$b['uri'], $b['name']];
        });

        return $rows;
    }

    private function join_uri(string $left, string $right): string
    {
        $left = trim($left, '/');
        $right = trim($right, '/');
        if ($left === '' && $right === '') {
            return '/';
        }
        if ($left === '') {
            return '/'.$right;
        }
        if ($right === '') {
            return '/'.$left;
        }

        return '/'.$left.'/'.$right;
    }

    private static function normalize_host(string $host): string
    {
        $host = strtolower($host);
        if ($host === '') {
            return '';
        }
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');

            return $end === false ? $host : substr($host, 0, $end + 1);
        }
        $colon = strrpos($host, ':');
        if ($colon === false) {
            return $host;
        }

        return substr($host, 0, $colon);
    }

    /**
     * Require route files from dumped webkernel_routes.php.
     */
    private function ensure_declared(): void
    {
        if (self::$declared_loaded) {
            return;
        }
        self::$declared_loaded = true;
        if (! \function_exists('base_path')) {
            return;
        }
        if (self::$configured_route_files !== null) {
            foreach (self::$configured_route_files as $path) {
                if (is_string($path) && is_file($path)) {
                    require $path;
                }
            }
            return;
        }
        $bootstrappers_file = base_path('runtime/cache/declarations/webkernel_route_bootstrappers.php');
        if (is_file($bootstrappers_file)) {
            $bootstrappers = require $bootstrappers_file;
            if (is_array($bootstrappers)) {
                self::$route_bootstrappers = $bootstrappers;
                foreach ($bootstrappers as $bootstrapper) {
                    if (! is_callable($bootstrapper)) {
                        throw new \RuntimeException('Invalid route bootstrapper declaration.');
                    }
                    $bootstrapper();
                }
            }
        }
        $file = base_path('runtime/cache/declarations/webkernel_routes.php');
        if (! is_file($file)) {
            return;
        }
        $routes = require $file;
        if (! is_array($routes)) {
            return;
        }
        foreach ($routes as $path) {
            if (is_string($path) && is_file($path)) {
                require $path;
            }
        }
    }

    /** Use route files declared by the site's config/site.php. */
    public static function useDeclaredRouteFiles(array $files, array $dependencies = []): void
    {
        self::flush();
        self::$configured_route_files = array_values(array_filter($files, 'is_string'));
        self::$configured_route_dependencies = array_values(array_filter($dependencies, 'is_string'));
    }

    /** @return list<string>|null */
    public static function configuredRouteFiles(): ?array
    {
        return self::$configured_route_files;
    }

    /** @return list<string>|null */
    public static function configuredRouteDependencies(): ?array
    {
        return self::$configured_route_dependencies;
    }
}
