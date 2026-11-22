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

namespace Webkernel\Identifiers\Uuid\Rfc4122;

use \Webkernel\Identifiers\Uuid\UuidInterface as BaseUuidInterface;

/**
 * A universally unique identifier (UUID), as defined in RFC 9562 (formerly RFC 4122)
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562 RFC 9562
 *
 * @immutable
 */
interface UuidInterface extends BaseUuidInterface
{
}
