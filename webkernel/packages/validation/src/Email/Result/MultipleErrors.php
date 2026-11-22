<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result;

use \Webkernel\Validation\Email\Result\Reason\EmptyReason;
use \Webkernel\Validation\Email\Result\Reason\Reason;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MultipleErrors extends InvalidEmail
{
    /**
     * @var Reason[]
     */
    private $reasons = [];

    public function __construct()
    {
    }

    public function addReason(Reason $reason) : void
    {
        $this->reasons[$reason->code()] = $reason;
    }

    /**
     * @return Reason[]
     */
    public function getReasons() : array
    {
        return $this->reasons;
    }

    public function reason() : Reason
    {
        return 0 !== count($this->reasons)
            ? current($this->reasons)
            : new EmptyReason();
    }

    public function description() : string
    {
        $description = '';
        foreach($this->reasons as $reason) {
            $description .= $reason->description() . PHP_EOL;
        }

        return $description;
    }

    public function code() : int
    {
        return 0;
    }
}
