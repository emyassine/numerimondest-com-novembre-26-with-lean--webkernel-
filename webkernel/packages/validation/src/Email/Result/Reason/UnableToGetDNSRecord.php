<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result\Reason;

/**
 * Used on SERVFAIL, TIMEOUT or other runtime and network errors
 */
class UnableToGetDNSRecord extends NoDNSRecord
{
    public function code() : int
    {
        return 3;
    }

    public function description() : string
    {
        return 'Unable to get DNS records for the host';
    }
}
