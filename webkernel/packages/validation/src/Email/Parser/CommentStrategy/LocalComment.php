<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Parser\CommentStrategy;

use \Webkernel\Validation\Email\EmailLexer;
use \Webkernel\Validation\Email\Result\Result;
use \Webkernel\Validation\Email\Result\ValidEmail;
use \Webkernel\Validation\Email\Warning\CFWSNearAt;
use \Webkernel\Validation\Email\Result\InvalidEmail;
use \Webkernel\Validation\Email\Result\Reason\ExpectingATEXT;
use \Webkernel\Validation\Email\Warning\Warning;

class LocalComment implements CommentStrategy
{
    /**
     * @var array<int, Warning>
     */
    private $warnings = [];

    public function exitCondition(EmailLexer $lexer, int $openedParenthesis): bool
    {
        return !$lexer->isNextToken(EmailLexer::S_AT);
    }

    public function endOfLoopValidations(EmailLexer $lexer): Result
    {
        if (!$lexer->isNextToken(EmailLexer::S_AT)) {
            return new InvalidEmail(new ExpectingATEXT('ATEX is not expected after closing comments'), $lexer->current->value);
        }
        $this->warnings[CFWSNearAt::CODE] = new CFWSNearAt();
        return new ValidEmail();
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
