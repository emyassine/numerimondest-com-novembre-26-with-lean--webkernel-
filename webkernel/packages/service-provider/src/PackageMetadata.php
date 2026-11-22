<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ServiceProvider;

use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;

/** Base contract for typed Composer package metadata. */
abstract class PackageMetadata
{
/** @var array{prefix?: string, catalog?: array<string, mixed>, artifacts?: list<class-string<ArtifactBuilder>>} */
    protected const array METADATA = [];

    /** @return array{prefix?: string, catalog?: array<string, mixed>, artifacts?: list<class-string<ArtifactBuilder>>} */
    final public static function metadata(): array
    {
        return static::METADATA;
    }
}
