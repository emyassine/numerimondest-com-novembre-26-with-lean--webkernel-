<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result;

class ValidEmail implements Result
{
    public function isValid(): bool
    {
        return true;
    }

    public function isInvalid(): bool
    {
        return false;
    }

    public function description(): string
    {
        return "Valid email";
    }

    public function code(): int
    {
        return 0;
    }

}
