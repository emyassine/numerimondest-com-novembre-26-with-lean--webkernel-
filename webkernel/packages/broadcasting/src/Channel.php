<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Broadcasting;

use \Webkernel\Broadcasting\Contracts\HasBroadcastChannel;
use Stringable;

class Channel implements Stringable
{
    /**
     * The channel's name.
     *
     * @var string
     */
    public $name;

    /**
     * Create a new channel instance.
     *
     * @param  \Webkernel\Broadcasting\Contracts\HasBroadcastChannel|string  $name
     */
    public function __construct($name)
    {
        $this->name = $name instanceof HasBroadcastChannel ? $name->broadcastChannel() : $name;
    }

    /**
     * Convert the channel instance to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
