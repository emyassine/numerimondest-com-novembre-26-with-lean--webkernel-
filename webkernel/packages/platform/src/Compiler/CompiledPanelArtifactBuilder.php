<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Compiler;

use RuntimeException;
use Webkernel\Platform\PanelRegistry;
use Webkernel\Platform\Webkernel\PanelRoutes;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;

/** Compiles opt-in panel templates into immutable deployment artifacts. */
final class CompiledPanelArtifactBuilder implements ArtifactBuilder
{
    public function artifactKey(): string
    {
        return 'compiled-panels';
    }

    public function dependencies(): array
    {
        return ['routes', 'views'];
    }

    public function build(ArtifactBuildContext $context): void
    {
        $directory = $context->artifactPath($this->artifactKey());
        PanelRoutes::registerProviders($context->rootPath.'/runtime/cache/declarations/webkernel_panels.php');
        $registry = app(PanelRegistry::class);
        $compiler = new TemplateCompiler();
        $manifest = [];
        foreach ($registry->all() as $panel_id => $panel) {
            foreach ($panel->getCompiledTemplates() as $name => $definition) {
                $relative_default = 'panels/'.$panel_id.'/'.$name.'.php';
                $compiler->publish($definition['default'], $directory.'/'.$relative_default);
                $role_paths = [];
                foreach ($definition['roles'] as $signature => $nodes) {
                    $variant_name = 'panels/'.$panel_id.'/'.$name.'.role-'.hash('sha256', $signature).'.php';
                    $compiler->publish($nodes, $directory.'/'.$variant_name);
                    $role_paths[$signature] = $variant_name;
                }
                $manifest[$panel_id][$name] = ['default' => $relative_default, 'roles' => $role_paths];
            }
        }

        $path = $directory.'/compiled_panels.php';
        $contents = '<?php return '.var_export($manifest, true).';'.PHP_EOL;
        $temporary_path = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        if (file_put_contents($temporary_path, $contents, LOCK_EX) !== strlen($contents) || ! rename($temporary_path, $path)) {
            @unlink($temporary_path);
            throw new RuntimeException('Unable to publish compiled panel template manifest.');
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}
