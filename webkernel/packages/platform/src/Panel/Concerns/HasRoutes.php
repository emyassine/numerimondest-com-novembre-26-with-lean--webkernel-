<?php

namespace Webkernel\Platform\Panel\Concerns;

use Closure;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Navigation\NavigationManager;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Arr;
use Webkernel\Route\Route;
use Webkernel\Utilities\Str;
use \Webkernel\Serialization\Closure\Serializers\Native;

trait HasRoutes
{
    /**
     * @var array<Closure | Native>
     */
    protected array $routes = [];

    /**
     * @var array<Closure | Native>
     */
    protected array $authenticatedRoutes = [];

    /**
     * @var array<Closure | Native>
     */
    protected array $tenantRoutes = [];

    /**
     * @var array<Closure | Native>
     */
    protected array $authenticatedTenantRoutes = [];

    protected string | Closure | null $homeUrl = null;

    /**
     * @var array<string>
     */
    protected array $domains = [];

    protected string $path = '';

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function domain(?string $domain): static
    {
        $this->domains(filled($domain) ? [$domain] : []);

        return $this;
    }

    /**
     * @param  array<string>  $domains
     */
    public function domains(array $domains): static
    {
        $this->domains = $domains;

        return $this;
    }

    public function homeUrl(string | Closure | null $url): static
    {
        $this->homeUrl = $url;

        return $this;
    }

    public function routes(?Closure $routes): static
    {
        $this->routes[] = $routes;

        return $this;
    }

    public function authenticatedRoutes(?Closure $routes): static
    {
        $this->authenticatedRoutes[] = $routes;

        return $this;
    }

    public function tenantRoutes(?Closure $routes): static
    {
        $this->tenantRoutes[] = $routes;

        return $this;
    }

    public function authenticatedTenantRoutes(?Closure $routes): static
    {
        $this->authenticatedTenantRoutes[] = $routes;

        return $this;
    }

    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route($this->generateRouteName($name), $parameters, $absolute);
    }

    public function generateRouteName(string $name): string
    {
        $domain = '';

        if (count($this->domains) > 1) {
            $domain = Platform::getCurrentDomain(testingDomain: Arr::first($this->domains)) . '.';
        }

        return "platform.{$this->getId()}.{$domain}{$name}";
    }

    /**
     * @return array<Closure | Native>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return array<Closure | Native>
     */
    public function getAuthenticatedRoutes(): array
    {
        return $this->authenticatedRoutes;
    }

    /**
     * @return array<Closure | Native>
     */
    public function getTenantRoutes(): array
    {
        return $this->tenantRoutes;
    }

    /**
     * @return array<Closure | Native>
     */
    public function getAuthenticatedTenantRoutes(): array
    {
        return $this->authenticatedTenantRoutes;
    }

    public function getHomeUrl(): ?string
    {
        return $this->evaluate($this->homeUrl);
    }

    /**
     * @return array<string>
     */
    public function getDomains(): array
    {
        return Arr::wrap($this->domains);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUrl(?Model $tenant = null): ?string
    {
        $hasTenancy = $this->hasTenancy();

        if ((! $tenant) && $hasTenancy && $this->auth()->hasUser()) {
            $tenant = Platform::getUserDefaultTenant($this->auth()->user());
        }

        if ($tenant && $this->hasTenantDomain()) {
            return $this->getRedirectUrl($tenant);
        }

        if (((! $hasTenancy) || $tenant) && Route::has($homeRouteName = $this->generateRouteName('home'))) {
            return route($homeRouteName, $tenant ? ['tenant' => $tenant] : []);
        }

        if ($tenant) {
            $tenantSlugAttribute = $this->getTenantSlugAttribute();
            $tenantRoutePrefix = $this->getTenantRoutePrefix() ?? '';

            if (filled($tenantRoutePrefix)) {
                $tenantRoutePrefix .= '/';
            }

            return url(Str::replaceEnd('/', '', $this->getPath()) . '/' . $tenantRoutePrefix . (filled($tenantSlugAttribute) ? $tenant->getAttributeValue($tenantSlugAttribute) : $tenant->getRouteKey()));
        }

        return url($this->getPath());
    }

    public function getRedirectUrl(?Model $tenant = null): ?string
    {
        if ((! $this->auth()->check()) && $this->hasLogin()) {
            return $this->getLoginUrl();
        }

        $hasTenancy = $this->hasTenancy();

        if ((! $tenant) && $hasTenancy && $this->auth()->hasUser()) {
            $tenant = Platform::getUserDefaultTenant($this->auth()->user());
        }

        if ((! $tenant) && $hasTenancy) {
            return ($this->hasTenantRegistration() && platform()->getTenantRegistrationPage()::canView()) ?
                $this->getTenantRegistrationUrl() :
                null;
        }

        if ($tenant) {
            $originalTenant = Platform::getTenant();
            Platform::setTenant($tenant, isQuiet: true);
        }

        $this->navigationManager = new NavigationManager;

        $navigation = $this->navigationManager->get();

        try {
            $firstGroup = Arr::first($navigation);

            if (! $firstGroup) {
                return url($this->getPath());
            }

            $firstItem = Arr::first($firstGroup->getItems());

            if (! $firstItem) {
                return url($this->getPath());
            }

            return $firstItem->getUrl();
        } finally {
            if ($tenant) {
                Platform::setTenant($originalTenant, isQuiet: true);
            }

            $this->navigationManager = null;
        }
    }
}
