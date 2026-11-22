<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Warning;

class LocalTooLong extends Warning
{
    public const CODE = 64;
    public const LOCAL_PART_LENGTH = 64;

    public function __construct()
    {
        $this->message = 'Local part is too long, exceeds 64 chars (octets)';
        $this->rfcNumber = 5322;
    }
}
