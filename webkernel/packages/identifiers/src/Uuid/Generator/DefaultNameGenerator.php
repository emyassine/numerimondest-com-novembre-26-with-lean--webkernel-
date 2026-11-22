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

namespace Webkernel\Identifiers\Uuid\Generator;

use \Webkernel\Identifiers\Uuid\Exception\NameException;
use \Webkernel\Identifiers\Uuid\UuidInterface;
use ValueError;

use function hash;

/**
 * DefaultNameGenerator generates strings of binary data based on a namespace, name, and hashing algorithm
 */
class DefaultNameGenerator implements NameGeneratorInterface
{
    /**
     * @pure
     */
    public function generate(UuidInterface $ns, string $name, string $hashAlgorithm): string
    {
        try {
            return hash($hashAlgorithm, $ns->getBytes() . $name, true);
        } catch (ValueError $e) {
            throw new NameException(
                message: sprintf('Unable to hash namespace and name with algorithm \'%s\'', $hashAlgorithm),
                previous: $e,
            );
        }
    }
}
