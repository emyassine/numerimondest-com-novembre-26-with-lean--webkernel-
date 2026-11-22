<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with the source code.

namespace Webkernel\ServiceProvider\Deployment;

use InvalidArgumentException;
use RuntimeException;

/** Supplies safe package-owned output paths for one deployment generation. */
final readonly class ArtifactBuildContext
{
    public function __construct(
        public string $rootPath,
        public string $generationPath,
    ) {}

    public function artifactPath(string $key): string
    {
        if (preg_match('/\A[a-z][a-z0-9_-]*\z/', $key) !== 1) {
            throw new InvalidArgumentException('Artifact keys must use lowercase letters, digits, hyphens, or underscores.');
        }

        $path = rtrim($this->generationPath, '/\\').'/'.$key;
        if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException('Unable to create deployment artifact directory: '.$path);
        }

        return $path;
    }
}
