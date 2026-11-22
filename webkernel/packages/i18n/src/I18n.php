<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\I18n;

/** Initializes catalog paths compiled from ServiceProvider::LANG_PATH. */
final class I18n
{
    private function __construct() {}

    /** @param $manifest array<string, list<string>> Prefix to language directory map. */
    public static function boot(array $manifest): void
    {
        $paths = [];
        foreach ($manifest as $directories) {
            foreach ($directories as $directory) {
                if (is_string($directory)) {
                    $paths[] = $directory;
                }
            }
        }
        Catalog::boot(array_values(array_unique($paths)));
    }
}
