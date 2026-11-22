<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with the source code.

namespace Webkernel\View\Deployment;

use RuntimeException;
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
        $declarations = $context->rootPath.'/runtime/cache/declarations/';
        $views = $this->manifest($declarations.'webkernel_views.php');
        $components = $this->manifest($declarations.'webkernel_components.php');
        $directives = $this->manifest($declarations.'webkernel_view_directives.php');
        View::boot($views, $components, $directives, $directory);
        $compiled = View::compileAll();
        foreach ($compiled as $view) {
            $path = View::engine()->compiled_path($view);
            if ($path === '' || ! is_file($path)) {
                throw new RuntimeException(sprintf('View compilation did not produce the "%s" template.', $view));
            }
        }
        if ($compiled === []) {
            throw new RuntimeException('No declared view templates were available to compile.');
        }
    }

    private function manifest(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $value = require $path;

        return is_array($value) ? $value : [];
    }
}
