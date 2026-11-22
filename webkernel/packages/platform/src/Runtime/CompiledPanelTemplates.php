<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use RuntimeException;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;

/** Loads and executes one precompiled template without constructing its component tree. */
final class CompiledPanelTemplates
{
    public function render(string $root_path, string $panel_id, string $template, Frame $frame): string|null
    {
        if (preg_match('/\A[a-z0-9][a-z0-9_-]*\z/', $panel_id) !== 1
            || preg_match('/\A[a-z][a-z0-9_-]*\z/', $template) !== 1) {
            throw new RuntimeException('Invalid compiled panel template identifier.');
        }
        $directory = DeploymentLock::artifactDirectory($root_path, 'compiled-panels');
        if ($directory === null) {
            return null;
        }
        $manifest_path = $directory.'/compiled_panels.php';
        if (! is_file($manifest_path)) {
            throw new RuntimeException('The deployed compiled panel manifest is missing.');
        }
        $manifest = require $manifest_path;
        if (! is_array($manifest) || ! is_array($manifest[$panel_id][$template] ?? null)) {
            return null;
        }
        $definition = $manifest[$panel_id][$template];
        $relative_path = null;
        if (($definition['roles'] ?? []) !== []) {
            $relative_path = $definition['roles'][$frame->roleSignature] ?? null;
            if (! is_string($relative_path)) {
                return null;
            }
        } else {
            $relative_path = $definition['default'] ?? null;
        }
        if (! is_string($relative_path)
            || str_starts_with($relative_path, '/')
            || in_array('..', explode('/', $relative_path), true)
            || preg_match('~\Apanels/[a-z0-9][a-z0-9_-]*/[a-z][a-z0-9_-]*(?:\.role-[a-f0-9]{64})?\.php\z~', $relative_path) !== 1) {
            throw new RuntimeException('The deployed compiled panel path is invalid.');
        }
        $path = $directory.'/'.$relative_path;
        if (! is_file($path)) {
            throw new RuntimeException('The deployed compiled panel template is missing.');
        }
        $renderer = require $path;
        if (! is_callable($renderer)) {
            throw new RuntimeException('The deployed compiled panel template is invalid.');
        }

        return $renderer($frame);
    }
}
