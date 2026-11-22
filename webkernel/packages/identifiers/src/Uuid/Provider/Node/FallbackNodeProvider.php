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

namespace Webkernel\Identifiers\Uuid\Provider\Node;

use \Webkernel\Identifiers\Uuid\Exception\NodeException;
use \Webkernel\Identifiers\Uuid\Provider\NodeProviderInterface;
use \Webkernel\Identifiers\Uuid\Type\Hexadecimal;

/**
 * FallbackNodeProvider retrieves the system node ID by stepping through a list of providers until a node ID can be obtained
 */
class FallbackNodeProvider implements NodeProviderInterface
{
    /**
     * @param iterable<NodeProviderInterface> $providers Array of node providers
     */
    public function __construct(private iterable $providers)
    {
    }

    public function getNode(): Hexadecimal
    {
        $lastProviderException = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->getNode();
            } catch (NodeException $exception) {
                $lastProviderException = $exception;

                continue;
            }
        }

        throw new NodeException(message: 'Unable to find a suitable node provider', previous: $lastProviderException);
    }
}
