<?php

namespace Webkernel\ORM\Concerns;

use Webkernel\ORM\Attributes\UseResource;
use \Webkernel\HttpFoundation\Resources\Json\JsonResource;
use Webkernel\Utilities\Str;
use LogicException;
use ReflectionClass;

trait TransformsToResource
{
    /**
     * Create a new resource object for the given resource.
     *
     * @param  class-string<\Webkernel\HttpFoundation\Resources\Json\JsonResource>|null  $resourceClass
     * @return \Webkernel\HttpFoundation\Resources\Json\JsonResource
     */
    public function toResource(?string $resourceClass = null): JsonResource
    {
        if ($resourceClass === null) {
            return $this->guessResource();
        }

        return $resourceClass::make($this);
    }

    /**
     * Guess the resource class for the model.
     *
     * @return \Webkernel\HttpFoundation\Resources\Json\JsonResource
     *
     * @throws \LogicException
     */
    protected function guessResource(): JsonResource
    {
        $resourceClass = $this->resolveResourceFromAttribute(static::class);

        if ($resourceClass !== null && class_exists($resourceClass)) {
            return $resourceClass::make($this);
        }

        foreach (static::guessResourceName() as $resourceClass) {
            if (is_string($resourceClass) && class_exists($resourceClass)) {
                return $resourceClass::make($this);
            }
        }

        throw new LogicException(sprintf('Failed to find resource class for model [%s].', get_class($this)));
    }

    /**
     * Guess the resource class name for the model.
     *
     * @return array{class-string<\Webkernel\HttpFoundation\Resources\Json\JsonResource>, class-string<\Webkernel\HttpFoundation\Resources\Json\JsonResource>}
     */
    public static function guessResourceName(): array
    {
        $modelClass = static::class;

        if (! Str::contains($modelClass, '\\Models\\')) {
            return [];
        }

        $relativeNamespace = Str::after($modelClass, '\\Models\\');

        $relativeNamespace = Str::contains($relativeNamespace, '\\')
            ? Str::beforeLast($relativeNamespace, '\\'.class_basename($modelClass))
            : '';

        $potentialResource = sprintf(
            '%s\\Http\\Resources\\%s%s',
            Str::before($modelClass, '\\Models'),
            (string) $relativeNamespace !== '' ? $relativeNamespace.'\\' : '',
            class_basename($modelClass)
        );

        return [$potentialResource.'Resource', $potentialResource];
    }

    /**
     * Get the resource class from the class attribute.
     *
     * @param  class-string<\Webkernel\HttpFoundation\Resources\Json\JsonResource>  $class
     * @return class-string<*>|null
     */
    protected function resolveResourceFromAttribute(string $class): ?string
    {
        if (! class_exists($class)) {
            return null;
        }

        $attributes = (new ReflectionClass($class))->getAttributes(UseResource::class);

        return $attributes !== []
            ? $attributes[0]->newInstance()->class
            : null;
    }
}
