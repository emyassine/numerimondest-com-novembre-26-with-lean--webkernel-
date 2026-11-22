<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email;

use \Webkernel\Validation\Email\Result\Result;
use \Webkernel\Validation\Email\Parser\LocalPart;
use \Webkernel\Validation\Email\Parser\DomainPart;
use \Webkernel\Validation\Email\Result\ValidEmail;
use \Webkernel\Validation\Email\Result\InvalidEmail;
use \Webkernel\Validation\Email\Warning\EmailTooLong;
use \Webkernel\Validation\Email\Result\Reason\NoLocalPart;

class EmailParser extends Parser
{
    public const EMAIL_MAX_LENGTH = 254;

    /**
     * @var string
     */
    protected $domainPart = '';

    /**
     * @var string
     */
    protected $localPart = '';

    public function parse(string $str): Result
    {
        $result = parent::parse($str);

        $this->addLongEmailWarning($this->localPart, $this->domainPart);

        return $result;
    }

    protected function preLeftParsing(): Result
    {
        if (!$this->hasAtToken()) {
            return new InvalidEmail(new NoLocalPart(), $this->lexer->current->value);
        }
        return new ValidEmail();
    }

    protected function parseLeftFromAt(): Result
    {
        return $this->processLocalPart();
    }

    protected function parseRightFromAt(): Result
    {
        return $this->processDomainPart();
    }

    private function processLocalPart(): Result
    {
        $localPartParser = new LocalPart($this->lexer);
        $localPartResult = $localPartParser->parse();
        $this->localPart = $localPartParser->localPart();
        $this->warnings = [...$localPartParser->getWarnings(), ...$this->warnings];

        return $localPartResult;
    }

    private function processDomainPart(): Result
    {
        $domainPartParser = new DomainPart($this->lexer);
        $domainPartResult = $domainPartParser->parse();
        $this->domainPart = $domainPartParser->domainPart();
        $this->warnings = [...$domainPartParser->getWarnings(), ...$this->warnings];

        return $domainPartResult;
    }

    public function getDomainPart(): string
    {
        return $this->domainPart;
    }

    public function getLocalPart(): string
    {
        return $this->localPart;
    }

    private function addLongEmailWarning(string $localPart, string $parsedDomainPart): void
    {
        if (strlen($localPart . '@' . $parsedDomainPart) > self::EMAIL_MAX_LENGTH) {
            $this->warnings[EmailTooLong::CODE] = new EmailTooLong();
        }
    }
}
