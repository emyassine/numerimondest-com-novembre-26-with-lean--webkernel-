<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation;

use \Webkernel\Validation\Email\Validation\DNSGetRecordWrapper;
use \Webkernel\Validation\Email\Validation\DNSRecords;

class FakeDnsGetRecordWrapper extends DNSGetRecordWrapper
{
    /**
     * Get a synthetic DNS record for the given host.
     *
     * @param  string  $host
     * @param  int  $type
     * @return \Webkernel\Validation\Email\Validation\DNSRecords
     */
    public function getRecords(string $host, int $type): DNSRecords
    {
        return new DNSRecords([['type' => 'A']]);
    }
}
