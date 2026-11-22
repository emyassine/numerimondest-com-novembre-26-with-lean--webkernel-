<?php

namespace Webkernel\ORM\Casts;

use Webkernel\ORM\Contracts\Castable;
use Webkernel\ORM\Contracts\CastsAttributes;

class AsArrayObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Webkernel\ORM\Contracts\CastsAttributes<\Webkernel\ORM\Casts\ArrayObject<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            public function __construct(protected array $arguments)
            {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = Json::decode($attributes[$key]);

                return is_array($data) ? new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                if (is_null($value) && in_array('nullable', $this->arguments, true)) {
                    return [$key => null];
                }

                return [$key => Json::encode($value)];
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                return $value->getArrayCopy();
            }
        };
    }

    /**
     * Specify that a null value assigned to the attribute should be persisted as a native SQL NULL instead of the JSON "null" literal.
     *
     * @return string
     */
    public static function nullable()
    {
        return static::class.':nullable';
    }
}
