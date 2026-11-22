<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Parser;

use \Webkernel\Validation\Email\EmailLexer;
use \Webkernel\Validation\Email\Result\InvalidEmail;
use \Webkernel\Validation\Email\Result\Reason\ConsecutiveDot;
use \Webkernel\Validation\Email\Result\Result;
use \Webkernel\Validation\Email\Result\ValidEmail;
use \Webkernel\Validation\Email\Warning\Warning;

abstract class PartParser
{
    /**
     * @var Warning[]
     */
    protected $warnings = [];

    /**
     * @var EmailLexer
     */
    protected $lexer;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    abstract public function parse(): Result;

    /**
     * @return Warning[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    protected function parseFWS(): Result
    {
        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse();
        $this->warnings = [...$this->warnings, ...$foldingWS->getWarnings()];
        return $resultFWS;
    }

    protected function checkConsecutiveDots(): Result
    {
        if ($this->lexer->current->isA(EmailLexer::S_DOT) && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ConsecutiveDot(), $this->lexer->current->value);
        }

        return new ValidEmail();
    }

    protected function escaped(): bool
    {
        $previous = $this->lexer->getPrevious();

        return $previous->isA(EmailLexer::S_BACKSLASH)
            && !$this->lexer->current->isA(EmailLexer::GENERIC);
    }
}
