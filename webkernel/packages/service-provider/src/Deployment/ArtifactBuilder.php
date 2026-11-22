<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with the source code.

namespace Webkernel\ServiceProvider\Deployment;

/** Builds one package-owned runtime artifact into an isolated deployment generation. */
interface ArtifactBuilder
{
    public function artifactKey(): string;

    /** @return list<string> */
    public function dependencies(): array;

    public function build(ArtifactBuildContext $context): void;
}
