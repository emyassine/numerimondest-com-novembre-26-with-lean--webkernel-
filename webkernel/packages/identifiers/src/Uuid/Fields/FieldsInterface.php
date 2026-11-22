<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Webkernel\Identifiers\Uuid\Fields;

use Serializable;

/**
 * UUIDs consist of unsigned integers, the bytes of which are separated into fields and arranged in a particular layout
 * defined by the specification for the variant
 *
 * @immutable
 */
interface FieldsInterface extends Serializable
{
    /**
     * Returns the bytes that comprise the fields
     *
     * @pure
     */
    public function getBytes(): string;
}
