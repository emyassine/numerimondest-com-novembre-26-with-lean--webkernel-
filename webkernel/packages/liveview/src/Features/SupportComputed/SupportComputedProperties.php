<?php

namespace Webkernel\Liveview\Features\SupportComputed;

use Webkernel\Liveview\ComponentHook;

use function Webkernel\Liveview\on;

class SupportComputedProperties extends ComponentHook
{
    static function provide()
    {
        on('__get', function ($target, $property, $returnValue) {
            $attribute = static::findComputedAttribute($target, $property);

            if ($attribute) {
                $attribute->handleMagicGet($returnValue);
            }
        });

        on('__unset', function ($target, $property) {
            $attribute = static::findComputedAttribute($target, $property);

            if ($attribute) {
                $attribute->handleMagicUnset();
            }
        });
    }

    public static function findComputedAttribute($target, $property)
    {
        $propertyName = (string) str($property)->camel();

        return $target->getAttributes()
            ->whereInstanceOf(BaseComputed::class)
            ->first(fn ($attr) => $attr->getName() === $propertyName);
    }

}
