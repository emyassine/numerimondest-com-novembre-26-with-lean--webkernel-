<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from League.Csv; see ../LICENSE.
/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Exporters\Csv\Serializer;

use ReflectionParameter;
use ReflectionProperty;

enum TypeCastingTargetType
{
    case PropertyName;
    case MethodFirstArgument;

    public static function fromAccessor(ReflectionParameter|ReflectionProperty $accessor): self
    {
        if ($accessor instanceof ReflectionProperty) {
            return self::PropertyName;
        }

        return self::MethodFirstArgument;
    }
}
