<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Validation;

use \Webkernel\Validation\Email\EmailLexer;
use \Webkernel\Validation\Email\Result\InvalidEmail;
use \Webkernel\Validation\Email\Warning\Warning;

interface EmailValidation
{
    /**
     * Returns true if the given email is valid.
     *
     * @param string     $email      The email you want to validate.
     * @param EmailLexer $emailLexer The email lexer.
     *
     * @return bool
     */
    public function isValid(string $email, EmailLexer $emailLexer) : bool;

    /**
     * Returns the validation error.
     *
     * @return InvalidEmail|null
     */
    public function getError() : ?InvalidEmail;

    /**
     * Returns the validation warnings.
     *
     * @return Warning[]
     */
    public function getWarnings() : array;
}
