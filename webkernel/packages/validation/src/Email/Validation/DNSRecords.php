<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Validation;

class DNSRecords
{
    /**
     * @param list<array<array-key, mixed>> $records
     * @param bool $error
     */
    public function __construct(private readonly array $records, private readonly bool $error = false)
    {
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function withError(): bool
    {
        return $this->error;
    }
}
