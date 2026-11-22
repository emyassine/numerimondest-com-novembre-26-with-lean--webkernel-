<?php declare(strict_types=1);

namespace Webkernel\Platform;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Resources\ResourceConfiguration;
use Webkernel\Platform\Support\Components\Component;
use Webkernel\Platform\Support\Facades\PlatformColor;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Facades\PlatformView;
use Webkernel\Platform\Compiler\TemplateNode;
use Webkernel\Route\Route;

class Panel extends Component
{
    use Panel\Concerns\CanGenerateResourceUrls;
    use Panel\Concerns\HasAssets;
    use Panel\Concerns\HasAuth;
    use Panel\Concerns\HasAvatars;
    use Panel\Concerns\HasBrandLogo;
    use Panel\Concerns\HasBrandName;
    use Panel\Concerns\HasBreadcrumbs;
    use Panel\Concerns\HasBroadcasting;
    use Panel\Concerns\HasColors;
    use Panel\Concerns\HasComponents;
    use Panel\Concerns\HasDarkMode;
    use Panel\Concerns\HasDatabaseTransactions;
    use Panel\Concerns\HasErrorNotifications;
    use Panel\Concerns\HasFavicon;
    use Panel\Concerns\HasFont;
    use Panel\Concerns\HasGlobalSearch;
    use Panel\Concerns\HasIcons;
    use Panel\Concerns\HasId;
    use Panel\Concerns\HasScope;
    use Panel\Concerns\HasMaxContentWidth;
    use Panel\Concerns\HasMiddleware;
    use Panel\Concerns\HasNavigation;
    use Panel\Concerns\HasNotifications;
    use Panel\Concerns\HasPlugins;
    use Panel\Concerns\HasRenderHooks;
    use Panel\Concerns\HasRoutes;
    use Panel\Concerns\HasSidebar;
    use Panel\Concerns\HasSpaMode;
    use Panel\Concerns\HasSubNavigation;
    use Panel\Concerns\HasTenancy;
    use Panel\Concerns\HasTheme;
    use Panel\Concerns\HasTopbar;
    use Panel\Concerns\HasTopNavigation;
    use Panel\Concerns\HasUnsavedChangesAlerts;
    use Panel\Concerns\HasUserMenu;

    protected bool | Closure $isDefault = false;

    protected bool $isTenancyBooted = false;

    /**
     * @var array<array-key, Closure>
     */
    protected array $bootCallbacks = [];

    /** @var array<string, array{default: list<TemplateNode>, roles: array<string, list<TemplateNode>>}> */
    protected array $compiledTemplates = [];

    public static function make(): static
    {
        return app(static::class)->configure();
    }

    /**
     * Declares a statically compiled template while preserving the fluent panel API.
     * Role variants are emitted as separate PHP artifacts during deployment.
     *
     * @param $name string
     * @param $default_nodes list<TemplateNode>
     * @param $role_variants array<string, list<TemplateNode>>
     * @return static
     */
    public function compiledTemplate(string $name, array $default_nodes, array $role_variants = []): static
    {
        if (preg_match('/\A[a-z][a-z0-9_-]*\z/', $name) !== 1 || isset($this->compiledTemplates[$name])) {
            throw new \InvalidArgumentException('Compiled template names must be unique lowercase identifiers.');
        }
        foreach ([$default_nodes, ...array_values($role_variants)] as $nodes) {
            if (! array_is_list($nodes)) {
                throw new \InvalidArgumentException('Compiled template node lists must be sequential.');
            }
            foreach ($nodes as $node) {
                if (! $node instanceof TemplateNode) {
                    throw new \InvalidArgumentException('Compiled templates accept only typed template nodes.');
                }
            }
        }
        foreach ($role_variants as $signature => $nodes) {
            if (! is_string($signature) || preg_match('/\A[a-zA-Z0-9_-]{1,96}\z/', $signature) !== 1) {
                throw new \InvalidArgumentException('Compiled role signatures must be safe stable identifiers.');
            }
        }
        $this->compiledTemplates[$name] = ['default' => $default_nodes, 'roles' => $role_variants];

        return $this;
    }

    /** @return array<string, array{default: list<TemplateNode>, roles: array<string, list<TemplateNode>>}> */
    public function getCompiledTemplates(): array
    {
        return $this->compiledTemplates;
    }

    public function default(bool | Closure $condition = true): static
    {
        $this->isDefault = $condition;

        return $this;
    }

    public function register(): void
    {
        $this->registerLivewireComponents();
        $this->registerLivewirePersistentMiddleware();

        if (str($this->getTenantDomain())->is(['{tenant}', '{tenant:*}'])) {
            // Route parameters may contain periods in this router.
            Route::pattern('tenant', '[a-z0-9.\-]+');
        }

        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            $this->registerAssets();
        }
    }

    public function boot(): void
    {
        $this->bootTenancy();

        $this->registerAssets();

        PlatformColor::register($this->getColors());

        PlatformIcon::register($this->getIcons());

        PlatformView::spa($this->hasSpaMode(), $this->hasSpaPrefetching());
        PlatformView::spaUrlExceptions($this->getSpaUrlExceptions());

        $this->registerRenderHooks();

        if ($this->hasDatabaseTransactions()) {
            Action::configureUsing(
                fn (Action $action) => $action->databaseTransaction(),
            );
        }

        foreach ($this->plugins as $plugin) {
            $plugin->boot($this);
        }

        foreach ($this->bootCallbacks as $callback) {
            $callback($this);
        }
    }

    public function bootTenancy(): void
    {
        if ($this->isTenancyBooted || (! $this->hasTenancy())) {
            return;
        }

        $resourceClasses = array_unique([
            ...$this->getResources(),
            ...array_map(
                static fn (ResourceConfiguration $configuration): string => $configuration->getResource(),
                $this->getResourceConfigurations(),
            ),
        ]);

        foreach ($resourceClasses as $resource) {
            $resource::observeTenancyModelCreation($this);
            $resource::registerTenancyModelGlobalScope($this);
        }

        $this->isTenancyBooted = true;
    }

    public function bootUsing(?Closure $callback): static
    {
        $this->bootCallbacks[] = $callback;

        return $this;
    }

    public function isDefault(): bool
    {
        return (bool) $this->evaluate($this->isDefault);
    }
}
