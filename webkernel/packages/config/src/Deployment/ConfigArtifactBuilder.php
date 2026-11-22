<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Config\Deployment;

use RuntimeException;
use Webkernel\Config\Config;
use Webkernel\Http;
use Webkernel\ServiceProvider\Deployment\ArtifactBuildContext;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;

/** Compiles configuration into this deployment generation. */
final class ConfigArtifactBuilder implements ArtifactBuilder
{
    public function artifactKey(): string
    {
        return 'config';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function build(ArtifactBuildContext $context): void
    {
        $directory = $context->artifactPath($this->artifactKey());
        Config::setPaths($context->rootPath, vendor_path(), $directory);
        Http::siteConfiguration($context->rootPath);
        Config::recompile();
        Http::publishSiteConfiguration($context->rootPath, $directory);
        if (! is_file($directory.'/config_compiled.php')) {
            throw new RuntimeException('Configuration compilation did not produce its cache file.');
        }
    }
}
