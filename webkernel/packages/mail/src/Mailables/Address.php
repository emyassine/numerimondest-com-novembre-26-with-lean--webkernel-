<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Mail\Mailables;

use InvalidArgumentException;

class Address
{
    /**
     * The recipient's email address.
     *
     * @var string
     */
    public $address;

    /**
     * The recipient's name.
     *
     * @var string|null
     */
    public $name;

    /**
     * Create a new address instance.
     *
     * @param  string  $address
     * @param  string|null  $name
     */
    public function __construct(string $address, ?string $name = null)
    {
        if (preg_match('/[\r\n]/', $address) > 0) {
            throw new InvalidArgumentException('Email addresses may not contain line break characters.');
        }

        $this->address = $address;
        $this->name = $name;
    }
}
