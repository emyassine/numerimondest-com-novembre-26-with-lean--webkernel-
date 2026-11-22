<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\DateTime\Exceptions;

use BadMethodCallException as BaseBadMethodCallException;
use Throwable;

class UnknownMethodException extends BaseBadMethodCallException implements BadMethodCallException
{
    /**
     * The method.
     *
     * @var string
     */
    protected $method;

    /**
     * Constructor.
     *
     * @param string         $method
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($method, $code = 0, ?Throwable $previous = null)
    {
        $this->method = $method;

        parent::__construct("Method $method does not exist.", $code, $previous);
    }

    /**
     * Get the method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
