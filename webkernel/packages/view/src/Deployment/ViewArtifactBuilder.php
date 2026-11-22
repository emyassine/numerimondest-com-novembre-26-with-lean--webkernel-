<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with the source code.

namespace Webkernel\View\Deployment;

use RuntimeException;
use Webkernel\Http;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;
use Webkernel\View\View;

/** Compiles declared templates into this deployment generation. */
final class ViewArtifactBuilder implements ArtifactBuilder
{
    public function artifactKey(): string
    {
        return 'views';
    }

    public function dependencies(): array
    {
        return ['config'];
    }

    public function build(ArtifactBuildContext $context): void
    {
        $directory = $context->artifactPath($this->artifactKey());
        $site = Http::siteConfiguration($context->rootPath);
        View::boot($site['views'] ?? [], $site['components'] ?? [], $site['view_directives'] ?? [], $directory);
        $compiled = View::compileAll();
        $compiled_map = [];
        foreach ($compiled as $view) {
            $path = View::engine()->compiled_path($view);
            if ($path === '' || ! is_file($path)) {
                throw new RuntimeException(sprintf('View compilation did not produce the "%s" template.', $view));
            }
            $compiled_map[$view] = $path;
        }
        if ($compiled === []) {
            throw new RuntimeException('No declared view templates were available to compile.');
        }
        $map_path = $directory.'/compiled-map.php';
        if (file_put_contents($map_path, "<?php declare(strict_types=1);\nreturn ".var_export($compiled_map, true).";\n", LOCK_EX) === false) {
            throw new RuntimeException('Unable to publish the compiled view map.');
        }
    }
}
