<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Parser;

use \Webkernel\Validation\Email\EmailLexer;
use \Webkernel\Validation\Email\Result\Result;
use \Webkernel\Validation\Email\Warning\QuotedPart;
use \Webkernel\Validation\Email\Result\InvalidEmail;
use \Webkernel\Validation\Email\Parser\CommentStrategy\CommentStrategy;
use \Webkernel\Validation\Email\Result\Reason\UnclosedComment;
use \Webkernel\Validation\Email\Result\Reason\UnOpenedComment;
use \Webkernel\Validation\Email\Warning\Comment as WarningComment;

class Comment extends PartParser
{
    /**
     * @var int
     */
    private $openedParenthesis = 0;

    /**
     * @var CommentStrategy
     */
    private $commentStrategy;

    public function __construct(EmailLexer $lexer, CommentStrategy $commentStrategy)
    {
        $this->lexer = $lexer;
        $this->commentStrategy = $commentStrategy;
    }

    public function parse(): Result
    {
        if ($this->lexer->current->isA(EmailLexer::S_OPENPARENTHESIS)) {
            $this->openedParenthesis++;
            if ($this->noClosingParenthesis()) {
                return new InvalidEmail(new UnclosedComment(), $this->lexer->current->value);
            }
        }

        if ($this->lexer->current->isA(EmailLexer::S_CLOSEPARENTHESIS)) {
            return new InvalidEmail(new UnOpenedComment(), $this->lexer->current->value);
        }

        $this->warnings[WarningComment::CODE] = new WarningComment();

        $moreTokens = true;
        while ($this->commentStrategy->exitCondition($this->lexer, $this->openedParenthesis) && $moreTokens) {

            if ($this->lexer->isNextToken(EmailLexer::S_OPENPARENTHESIS)) {
                $this->openedParenthesis++;
            }
            $this->warnEscaping();
            if ($this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
                $this->openedParenthesis--;
            }
            $moreTokens = $this->lexer->moveNext();
        }

        if ($this->openedParenthesis >= 1) {
            return new InvalidEmail(new UnclosedComment(), $this->lexer->current->value);
        }
        if ($this->openedParenthesis < 0) {
            return new InvalidEmail(new UnOpenedComment(), $this->lexer->current->value);
        }

        $finalValidations = $this->commentStrategy->endOfLoopValidations($this->lexer);

        $this->warnings = [...$this->warnings, ...$this->commentStrategy->getWarnings()];

        return $finalValidations;
    }


    /**
     * @return void
     */
    private function warnEscaping(): void
    {
        //Backslash found
        if (!$this->lexer->current->isA(EmailLexer::S_BACKSLASH)) {
            return;
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
            return;
        }

        $this->warnings[QuotedPart::CODE] =
            new QuotedPart($this->lexer->getPrevious()->type, $this->lexer->current->type);
    }

    private function noClosingParenthesis(): bool
    {
        try {
            $this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            return false;
        } catch (\RuntimeException $e) {
            return true;
        }
    }
}
