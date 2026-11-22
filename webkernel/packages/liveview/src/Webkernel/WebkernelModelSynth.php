<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Webkernel;

use Webkernel\Liveview\Mechanisms\HandleComponents\Synthesizers\Synth;
use Webkernel\Liveview\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkernel\Models\Model;

/** Livewire snapshot support for Webkernel's non-Eloquent active-record models. */
final class WebkernelModelSynth extends Synth
{
    public static $key = 'wkm';

    public static function match($target): bool
    {
        return $target instanceof Model;
    }

    public function dehydrate($target): array
    {
        $meta = ['class' => $target::class];
        if ($target->exists() && ($key = $target->getKey()) !== null) {
            $meta['key'] = $key;
        }

        return [null, $meta];
    }

    public function hydrate($data, $meta): Model
    {
        $class = $meta['class'] ?? null;
        if (! is_string($class) || ! is_a($class, Model::class, true)) {
            throw new \UnexpectedValueException('Livewire received an invalid Webkernel model class.');
        }
        if (! array_key_exists('key', $meta)) {
            return new $class();
        }

        $key = $meta['key'];
        if (! is_int($key) && ! is_string($key)) {
            throw new \UnexpectedValueException('Livewire received an invalid Webkernel model key.');
        }
        $resolved = Runtime::resolve(PersistentMiddleware::class)->getResolvedRouteModel($class, $key);
        if ($resolved instanceof $class) { return $resolved; }

        $model = $class::find($key);
        if ($model === null) {
            throw new NotFoundHttpException('The Webkernel model referenced by this Livewire snapshot no longer exists.');
        }

        return $model;
    }

    public function get(&$target, $key): never
    {
        throw new \LogicException('Webkernel model properties cannot be accessed directly through Livewire.');
    }

    public function set(&$target, $key, $value, $pathThusFar, $fullPath): never
    {
        throw new \LogicException('Webkernel model properties cannot be changed directly through Livewire.');
    }

    public function call($target, $method, $params, $addEffect): never
    {
        throw new \LogicException('Webkernel model methods cannot be called directly through Livewire.');
    }
}
