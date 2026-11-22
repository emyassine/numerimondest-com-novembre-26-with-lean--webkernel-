<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result\Reason;

class DomainAcceptsNoMail implements Reason
{
    public function code() : int
    {
        return 154;
    }

    public function description() : string
    {
        return 'Domain accepts no mail (Null MX, RFC7505)';
    }
}
