<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

/** One polling result. Checks against this result perform no I/O. */
final readonly class ChangeSet
{
    /**
     * Creates an immutable source observation.
     *
     * @param $changes array<string, bool> Changed target identifiers and source paths.
     * @param $fingerprints array<string, string> Current target fingerprints.
     */
    public function __construct(
        private array $changes,
        private array $fingerprints,
    ) {
    }

    /**
     * Checks one target identifier or absolute watched path.
     *
     * @param $id_or_path string Target key or source path.
     * @return bool True when the value differs from the preceding observation.
     */
    public function hasChanged(string $id_or_path): bool
    {
        return $this->changes[$id_or_path] ?? false;
    }

    /**
     * Returns the target fingerprint captured by this observation.
     *
     * @param $id string Target identifier.
     * @return string|null Null when the target was not part of this observation.
     */
    public function fingerprint(string $id): ?string
    {
        return $this->fingerprints[$id] ?? null;
    }

    /**
     * Lists changed target identifiers and source paths.
     *
     * @return list<string>
     */
    public function changed(): array
    {
        $changed = [];
        foreach ($this->changes as $key => $has_changed) {
            if ($has_changed) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}
