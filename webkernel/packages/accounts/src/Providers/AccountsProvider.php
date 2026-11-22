<?php declare(strict_types=1);

namespace Webkernel\Accounts\Providers;

use Webkernel\Accounts\AuthManager;
use Webkernel\Accounts\Contracts\Factory as AuthFactory;
use Webkernel\Cookie\CookieJar;
use Webkernel\Cryptography\ConfiguredHasher;
use Webkernel\Events\Dispatcher;
use Webkernel\Route\Redirector;
use Webkernel\ServiceProvider;
use Webkernel\Session\NativeStore;

final class AccountsProvider extends ServiceProvider
{
    public const array BINDINGS = [
        AuthFactory::class => [
            'alias' => AuthManager::class,
        ],
        AuthManager::class => [
            'concrete' => AuthManager::class,
            'lifetime' => 'scoped',
        ],
        'redirect' => [
            'concrete' => Redirector::class,
            'lifetime' => 'scoped',
        ],
        'hash' => [
            'concrete' => ConfiguredHasher::class,
            'lifetime' => 'scoped',
        ],
        'events' => [
            'concrete' => Dispatcher::class,
            'lifetime' => 'scoped',
        ],
        'cookie' => [
            'concrete' => CookieJar::class,
            'lifetime' => 'scoped',
        ],
        'session.store' => [
            'concrete' => NativeStore::class,
            'lifetime' => 'scoped',
        ],
    ];
}
