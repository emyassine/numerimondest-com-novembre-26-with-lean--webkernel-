<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Deployment;

use RuntimeException;
use Webkernel\Http;
use Webkernel\Route\Compile\Cache;
use Webkernel\Route\Route;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;

/** Compiles route tables for the default host and every declared domain pattern. */
final class RouteArtifactBuilder implements ArtifactBuilder
{
    public function artifactKey(): string
    {
        return 'routes';
    }

    public function dependencies(): array
    {
        return ['config'];
    }

    public function build(ArtifactBuildContext $context): void
    {
        $directory = $context->artifactPath($this->artifactKey());
        $site = Http::siteConfiguration($context->rootPath);
        Route::useDeclaredRouteFiles($site['routes'] ?? [], $site['route_dependencies'] ?? []);
        $compiled = Route::compile_for_deployment();
        $path = $directory.'/compiled_routes.php';
        Cache::write($path, $compiled['default'], [
            'compiled_at' => time(),
            'host' => '',
            'files' => Cache::fingerprints(),
            'hosts' => $compiled['domains'],
            'runtime' => $compiled['runtime'],
        ]);
        if (! is_file($path)) {
            throw new RuntimeException('Route compilation did not produce its cache file.');
        }
    }
}
