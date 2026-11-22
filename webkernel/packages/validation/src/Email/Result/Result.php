<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result;

interface Result
{
    /**
     * Is validation result valid?
     * 
     */
    public function isValid(): bool;

    /**
     * Is validation result invalid?
     * Usually the inverse of isValid()
     * 
     */
    public function isInvalid(): bool;

    /**
     * Short description of the result, human readable.
     * 
     */
    public function description(): string;

    /**
     * Code for user land to act upon.
     * 
     */
    public function code(): int;
}
