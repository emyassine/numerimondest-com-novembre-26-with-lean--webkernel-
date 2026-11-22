<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use InvalidArgumentException;
use Webkernel\Utilities\Contracts\Arrayable;
use Webkernel\View\Contracts\Factory as FactoryContract;
use Webkernel\View\FileViewFinder;
use Webkernel\View\View;

/** Factory API backed by the canonical Webkernel renderer. */
final class Factory implements FactoryContract
{
    public function exists($view) { return View::exists($view); }
    public function file($path, $data = [], $mergeData = []) { return View::file($path, $this->data($data) + $mergeData); }
    public function make($view, $data = [], $mergeData = []) { return View::make($view, $this->data($data) + $mergeData); }
    public function share($key, $value = null) { View::share($key, $value); }
    public function composer($views, $callback) { return View::composer($views, $callback); }
    public function creator($views, $callback) { return View::creator($views, $callback); }
    public function addNamespace($namespace, $hints) { foreach ((array) $hints as $path) { View::addNamespace($namespace, $path); } return $this; }
    public function prependNamespace($namespace, $hints) { View::prependNamespace($namespace, (array) $hints); return $this; }
    public function replaceNamespace($namespace, $hints) { View::replaceNamespace($namespace, (array) $hints); return $this; }
    public function getFinder(): FileViewFinder { return new FileViewFinder(); }
    public function getShared(): array { return View::sharedData(); }
    public function shared($key, $default = null): mixed { return View::shared($key, $default); }
    public function addLocation($path): void { View::add_location($path); }
    public function getEngineResolver(): mixed { return View::engine(); }
    public function __call(string $method, array $arguments): mixed { return View::engine()->{$method}(...$arguments); }
    private function data(mixed $data): array { return $data instanceof Arrayable ? $data->toArray() : (array) $data; }
}
