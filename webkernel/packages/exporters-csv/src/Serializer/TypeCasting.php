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

/**
 * @template TValue
 *
 * @method TypeCastingInfo info() the name of the property the value will be assigned to
 */
interface TypeCasting
{
    /**
     * @throws TypeCastingFailed
     *
     * @return TValue
     */
    public function toVariable(mixed $value): mixed;

    /**
     * Accepts additional parameters to configure the class
     * Parameters should be scalar value, null or array containing
     * only scalar value and null.
     *
     * @throws MappingFailed
     */
    public function setOptions(): void;
}
