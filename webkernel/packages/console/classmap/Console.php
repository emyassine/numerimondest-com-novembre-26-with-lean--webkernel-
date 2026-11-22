<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use Webkernel\Config\Config;
use Webkernel\Console\Dispatcher;
use Webkernel\Console\Input\ArgvInput;
use Webkernel\Container\Container;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;

/** CLI entry point used by the application executable. */
final class Console
{
    /** @param list<string> $argv */
    public static function run(array $argv): never
    {
        $root_path = base_path();
        $vendor_path = vendor_path();

        // A stale deployment receipt must not prevent the CLI from reaching
        // `deploy`, which is the command that repairs missing artifacts.
        $deployment = DeploymentLock::read($root_path);
        if ($deployment !== null) {
            foreach ($deployment['artifacts'] as $relative_path) {
                if (! is_dir($root_path.'/'.$relative_path)) {
                    DeploymentLock::beginBuild();
                    break;
                }
            }
        }

        Config::boot($root_path, $vendor_path, $root_path . '/runtime/cache');
        $container = Container::boot(self::manifest(base_path('runtime/cache/declarations/webkernel_bindings.php')));
        $container->beginScope();

        exit($container->make(Dispatcher::class)->handle(new ArgvInput($argv))->value);
    }

    /** @return array<mixed> */
    private static function manifest(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $manifest = require $path;

        return is_array($manifest) ? $manifest : [];
    }
}
