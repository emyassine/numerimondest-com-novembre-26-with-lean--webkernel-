<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

/** Identifies a completed artifact without interpreting its contents. */
final readonly class Publication
{
    /**
     * Creates a reference to an immutable compiled artifact.
     *
     * @param $id string Consumer-defined target identifier.
     * @param $fingerprint string Digest of the registered sources and compiler revision.
     * @param $version string SHA-256 digest of the artifact bytes.
     * @param $path string Absolute artifact path.
     */
    public function __construct(
        public string $id,
        public string $fingerprint,
        public string $version,
        public string $path,
    ) {
    }
}
