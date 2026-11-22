<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Warning;

use UnitEnum;

class QuotedPart extends Warning
{
    public const CODE = 36;

    /**
     * @param UnitEnum|string|int|null $prevToken
     * @param UnitEnum|string|int|null $postToken
     */
    public function __construct($prevToken, $postToken)
    {
        if ($prevToken instanceof UnitEnum) {
            $prevToken = $prevToken->name;
        }

        if ($postToken instanceof UnitEnum) {
            $postToken = $postToken->name;
        }

        $this->message = "Deprecated Quoted String found between $prevToken and $postToken";
    }
}
