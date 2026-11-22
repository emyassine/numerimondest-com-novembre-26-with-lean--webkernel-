<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue;

use InvalidArgumentException;

class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * The value that failed to decode.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new exception instance.
     *
     * @param  string|null  $message
     * @param  mixed  $value
     */
    public function __construct($message = null, $value = null)
    {
        parent::__construct($message ?: json_last_error());

        $this->value = $value;
    }
}
