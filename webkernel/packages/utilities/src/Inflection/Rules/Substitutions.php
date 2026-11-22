<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Inflection\Rules;

use \Webkernel\Utilities\Inflection\WordInflector;

use function strtolower;
use function strtoupper;
use function substr;

class Substitutions implements WordInflector
{
    /** @var Substitution[] */
    private $substitutions;

    public function __construct(Substitution ...$substitutions)
    {
        foreach ($substitutions as $substitution) {
            $this->substitutions[$substitution->getFrom()->getWord()] = $substitution;
        }
    }

    public function getFlippedSubstitutions(): Substitutions
    {
        $substitutions = [];

        foreach ($this->substitutions as $substitution) {
            $substitutions[] = new Substitution(
                $substitution->getTo(),
                $substitution->getFrom()
            );
        }

        return new Substitutions(...$substitutions);
    }

    public function inflect(string $word): string
    {
        $lowerWord = strtolower($word);

        if (isset($this->substitutions[$lowerWord])) {
            $firstLetterUppercase = $lowerWord[0] !== $word[0];

            $toWord = $this->substitutions[$lowerWord]->getTo()->getWord();

            if ($firstLetterUppercase) {
                return strtoupper($toWord[0]) . substr($toWord, 1);
            }

            return $toWord;
        }

        return $word;
    }
}
