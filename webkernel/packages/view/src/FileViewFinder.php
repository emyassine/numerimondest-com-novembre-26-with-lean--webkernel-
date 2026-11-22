<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use Webkernel\View\View;

/** Reads the same compiled namespace declarations as Webkernel's view engine. */
final class FileViewFinder
{
    public function find(string $name): string { return View::engine()->template_file($name); }
    public function getHints(): array { return View::locations(); }
    public function getPaths(): array { return array_merge([], ...array_values(View::locations())); }
    public function addNamespace(string $name, array|string $paths): void { foreach ((array) $paths as $path) { View::addNamespace($name, $path); } }
    public function prependNamespace(string $name, array|string $paths): void { View::prependNamespace($name, (array) $paths); }
}
