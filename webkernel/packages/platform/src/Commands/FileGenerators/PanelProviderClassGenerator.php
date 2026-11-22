<?php

namespace Webkernel\Platform\Commands\FileGenerators;

use Webkernel\Platform\Http\Middleware\Authenticate;
use Webkernel\Platform\Http\Middleware\AuthenticateSession;
use Webkernel\Platform\Http\Middleware\DispatchServingPlatformEvent;
use Webkernel\Platform\Pages\Dashboard;
use Webkernel\Platform\Panel;
use Webkernel\Platform\PanelProvider;
use Webkernel\Platform\Support\Colors\Color;
use Webkernel\Platform\Support\Commands\FileGenerators\ClassGenerator;
use Webkernel\Platform\Widgets\AccountWidget;
use Webkernel\Platform\Widgets\PlatformInfoWidget;
use \Webkernel\Cookie\Middleware\AddQueuedCookiesToResponse;
use \Webkernel\Cookie\Middleware\EncryptCookies;
use \Webkernel\Liveview\Providers\WebMiddleware as PreventRequestForgery;
use \Webkernel\Liveview\Providers\WebMiddleware as VerifyCsrfToken;
use \Webkernel\Route\Middleware\SubstituteBindings;
use \Webkernel\Session\Middleware\StartSession;
use Webkernel\Utilities\Str;
use \Webkernel\View\Middleware\ShareErrorsFromSession;
use Webkernel\CodeGenerator\ClassType;
use Webkernel\CodeGenerator\Literal;
use Webkernel\CodeGenerator\Method;

class PanelProviderClassGenerator extends ClassGenerator
{
    final public function __construct(
        protected string $fqn,
        protected string $id,
        protected bool $isDefault = false,
    ) {}

    public function getNamespace(): string
    {
        return $this->extractNamespace($this->getFqn());
    }

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        return [
            Panel::class,
            $this->getExtends(),
            Color::class,
            Dashboard::class,
            AccountWidget::class,
            PlatformInfoWidget::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            $this->getCsrfMiddlewareClass(),
            SubstituteBindings::class,
            DispatchServingPlatformEvent::class,
            Authenticate::class,
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return PanelProvider::class;
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addPanelMethodToClass($class);
    }

    protected function addPanelMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('panel')
            ->setPublic()
            ->setReturnType(Panel::class)
            ->setBody($this->generatePanelMethodBody());
        $method->addParameter('panel')
            ->setType(Panel::class);

        $this->configurePanelMethod($method);
    }

    public function generatePanelMethodBody(): string
    {
        $isDefault = $this->isDefault();

        $defaultOutput = $isDefault
            ? <<<'PHP'

                    ->default()
                PHP
            : '';

        $loginOutput = $isDefault
            ? <<<'PHP'

                    ->login()
                PHP
            : '';

        $id = $this->getId();

        $componentsDirectory = $isDefault ? '' : (Str::studly($id) . '/');
        $componentsNamespace = $isDefault ? '' : (Str::studly($id) . '\\');

        $rootNamespace = app()->getNamespace();

        return new Literal(
            <<<PHP
                return \$panel{$defaultOutput}
                    ->id(?)
                    ->path(?){$loginOutput}
                    ->colors([
                        'primary' => {$this->simplifyFqn(Color::class)}::Amber,
                    ])
                    ->discoverResources(in: app_path('Platform/{$componentsDirectory}Resources'), for: '{$rootNamespace}Webkernel\Platform\\{$componentsNamespace}Resources')
                    ->discoverPages(in: app_path('Platform/{$componentsDirectory}Pages'), for: '{$rootNamespace}Webkernel\Platform\\{$componentsNamespace}Pages')
                    ->pages([
                        {$this->simplifyFqn(Dashboard::class)}::class,
                    ])
                    ->discoverWidgets(in: app_path('Platform/{$componentsDirectory}Widgets'), for: '{$rootNamespace}Webkernel\Platform\\{$componentsNamespace}Widgets')
                    ->widgets([
                        {$this->simplifyFqn(AccountWidget::class)}::class,
                        {$this->simplifyFqn(PlatformInfoWidget::class)}::class,
                    ])
                    ->middleware([
                        {$this->simplifyFqn(EncryptCookies::class)}::class,
                        {$this->simplifyFqn(AddQueuedCookiesToResponse::class)}::class,
                        {$this->simplifyFqn(StartSession::class)}::class,
                        {$this->simplifyFqn(AuthenticateSession::class)}::class,
                        {$this->simplifyFqn(ShareErrorsFromSession::class)}::class,
                        {$this->simplifyFqn($this->getCsrfMiddlewareClass())}::class,
                        {$this->simplifyFqn(SubstituteBindings::class)}::class,
                        {$this->simplifyFqn(DispatchServingPlatformEvent::class)}::class,
                    ])
                    ->authMiddleware([
                        {$this->simplifyFqn(Authenticate::class)}::class,
                    ]);
                PHP,
            [$id, $id],
        );
    }

    protected function configurePanelMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @return class-string
     */
    protected function getCsrfMiddlewareClass(): string
    {
        return class_exists(PreventRequestForgery::class)
            ? PreventRequestForgery::class
            : VerifyCsrfToken::class;
    }
}
