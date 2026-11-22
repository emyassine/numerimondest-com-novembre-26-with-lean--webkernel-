<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result;

use \Webkernel\Validation\Email\Result\Reason\Reason;

class InvalidEmail implements Result
{
    /**
     * @var string
     */
    private string $token;

    /**
     * @var Reason
     */
    protected Reason $reason;

    public function __construct(Reason $reason, string $token)
    {
        $this->token = $token;
        $this->reason = $reason;
    }

    public function isValid(): bool
    {
        return false;
    }

    public function isInvalid(): bool
    {
        return true;
    }

    public function description(): string
    {
        return $this->reason->description() . " in char " . $this->token;
    }

    public function code(): int
    {
        return $this->reason->code();
    }

    public function reason(): Reason
    {
        return $this->reason;
    }
}
