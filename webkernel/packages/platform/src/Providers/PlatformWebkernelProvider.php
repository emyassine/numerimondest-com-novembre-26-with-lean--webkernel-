<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Providers;

use Webkernel\Platform\Auth\Http\Responses\BlockEmailChangeVerificationResponse;
use Webkernel\Platform\Auth\Http\Responses\Contracts\BlockEmailChangeVerificationResponse as BlockEmailChangeVerificationResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\EmailChangeVerificationResponse as EmailChangeVerificationResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Webkernel\Platform\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Webkernel\Platform\Auth\Http\Responses\EmailChangeVerificationResponse;
use Webkernel\Platform\Auth\Http\Responses\EmailVerificationResponse;
use Webkernel\Platform\Auth\Http\Responses\LoginResponse;
use Webkernel\Platform\Auth\Http\Responses\LogoutResponse;
use Webkernel\Platform\Auth\Http\Responses\PasswordResetResponse;
use Webkernel\Platform\Auth\Http\Responses\RegistrationResponse;
use Webkernel\Platform\Support\Assets\AssetManager;
use Webkernel\Platform\Support\Contracts\LoadingIndicator;
use Webkernel\Platform\Support\View\DefaultLoadingIndicator;
use Webkernel\Platform\PlatformManager;
use Webkernel\Platform\PanelRegistry;
use Webkernel\Platform\Webkernel\PlatformRenderHooks;
use Webkernel\Platform\Webkernel\PlatformRouteMiddleware;
use Webkernel\Platform\Webkernel\PanelRoutes;
use Webkernel\Platform\Commands\PanelRuntimeProbeCommand;
use Webkernel\ServiceProvider;

final class PlatformWebkernelProvider extends ServiceProvider
{
    public const array ROUTE_BOOTSTRAPPERS = [
        [PlatformRouteMiddleware::class, 'register'],
        [PlatformRenderHooks::class, 'register'],
        [PanelRoutes::class, 'registerProviders'],
    ];

    public const array RUNTIME_BOOTSTRAPPERS = [
        [PlatformRenderHooks::class, 'register'],
        [PanelRoutes::class, 'registerProviders'],
    ];

    public const array ROUTES = [__DIR__.'/../../routes/web.php'];

    public const array COMMANDS = [PanelRuntimeProbeCommand::class];

    public const array PUBLIC_ASSETS = [__DIR__.'/../../dist'];

    public const array BINDINGS = [
        'platform' => ['concrete' => PlatformManager::class, 'lifetime' => 'scoped'],
        PanelRegistry::class => ['concrete' => PanelRegistry::class, 'lifetime' => 'scoped'],
        PlatformManager::class => ['concrete' => PlatformManager::class, 'lifetime' => 'scoped'],
        AssetManager::class => [
            'concrete' => AssetManager::class,
            'lifetime' => 'scoped',
        ],
        LoadingIndicator::class => [
            'concrete' => DefaultLoadingIndicator::class,
            'lifetime' => 'scoped',
        ],
        BlockEmailChangeVerificationResponseContract::class => ['concrete' => BlockEmailChangeVerificationResponse::class, 'lifetime' => 'transient'],
        EmailChangeVerificationResponseContract::class => ['concrete' => EmailChangeVerificationResponse::class, 'lifetime' => 'transient'],
        EmailVerificationResponseContract::class => ['concrete' => EmailVerificationResponse::class, 'lifetime' => 'transient'],
        LoginResponseContract::class => ['concrete' => LoginResponse::class, 'lifetime' => 'transient'],
        LogoutResponseContract::class => ['concrete' => LogoutResponse::class, 'lifetime' => 'transient'],
        PasswordResetResponseContract::class => ['concrete' => PasswordResetResponse::class, 'lifetime' => 'transient'],
        RegistrationResponseContract::class => ['concrete' => RegistrationResponse::class, 'lifetime' => 'transient'],
    ];

    public const array VIEW_DIRECTIVES = [
        'platformScripts' => [ViewDirectives::class, 'scripts'],
        'platformStyles' => [ViewDirectives::class, 'styles'],
        'captureSlots' => [ViewDirectives::class, 'captureSlots'],
    ];

    public const array VIEWS = [
        'webkernel-platform' => [__DIR__ . '/../../resources/views'],
        'webkernel-actions' => [__DIR__ . '/../../../platform-actions/resources/views'],
        'webkernel-forms' => [__DIR__ . '/../../../platform-forms/resources/views'],
        'webkernel-infolists' => [__DIR__ . '/../../../platform-infolists/resources/views'],
        'webkernel-notifications' => [__DIR__ . '/../../../platform-notifications/resources/views'],
        'webkernel-schemas' => [__DIR__ . '/../../../platform-schemas/resources/views'],
        'webkernel' => [__DIR__ . '/../../../platform-support/resources/views'],
        'webkernel-tables' => [__DIR__ . '/../../../platform-tables/resources/views'],
        'webkernel-widgets' => [__DIR__ . '/../../../platform-widgets/resources/views'],
    ];

    public const array COMPONENTS = [
        'webkernel-actions' => [__DIR__ . '/../../../platform-actions/resources/views/components'],
        'webkernel-forms' => [__DIR__ . '/../../../platform-forms/resources/views/components'],
        'webkernel-infolists' => [__DIR__ . '/../../../platform-infolists/resources/views/components'],
        'webkernel-schemas' => [__DIR__ . '/../../../platform-schemas/resources/views/components'],
        'webkernel-tables' => [__DIR__ . '/../../../platform-tables/resources/views/components'],
        'webkernel-widgets' => [__DIR__ . '/../../../platform-widgets/resources/views/components'],
        // Keep Platform components separate from Webkernel's own icon components.
        'webkernel-platform' => [
            __DIR__ . '/../../../platform-support/resources/views/components',
            __DIR__ . '/../../resources/views/components',
            __DIR__ . '/../../../platform-actions/resources/views/components',
            __DIR__ . '/../../../platform-forms/resources/views/components',
            __DIR__ . '/../../../platform-infolists/resources/views/components',
            __DIR__ . '/../../../platform-schemas/resources/views/components',
            __DIR__ . '/../../../platform-tables/resources/views/components',
            __DIR__ . '/../../../platform-widgets/resources/views/components',
        ],
    ];
}
