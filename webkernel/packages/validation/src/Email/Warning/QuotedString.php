<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Warning;

class QuotedString extends Warning
{
    public const CODE = 11;

    /**
     * @param string|int $prevToken
     * @param string|int $postToken
     */
    public function __construct($prevToken, $postToken)
    {
        $this->message = "Quoted String found between $prevToken and $postToken";
    }
}
