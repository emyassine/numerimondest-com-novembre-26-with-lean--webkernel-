<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Validation;

class DNSGetRecordWrapper
{
    /**
     * @param string $host
     * @param int $type
     *
     * @return DNSRecords
     */
    public function getRecords(string $host, int $type): DNSRecords
    {
        // A workaround to fix https://bugs.php.net/bug.php?id=73149
        set_error_handler(
            static function (int $errorLevel, string $errorMessage): never {
                throw new \RuntimeException("Unable to get DNS record for the host: $errorMessage");
            }
        );
        try {
            // Get all MX, A and AAAA DNS records for host
            return new DNSRecords(dns_get_record($host, $type));
        } catch (\RuntimeException $exception) {
            return new DNSRecords([], true);
        } finally {
            restore_error_handler();
        }
    }
}
