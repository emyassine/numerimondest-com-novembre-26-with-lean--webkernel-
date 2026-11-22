<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Inflection;

use \Webkernel\Utilities\Inflection\Rules\Ruleset;

use function array_merge;

/**
 * Inflects based on multiple rulesets.
 *
 * Rules:
 * - If the word matches any uninflected word pattern, it is not inflected
 * - The first ruleset that returns a different value for an irregular word wins
 * - The first ruleset that returns a different value for a regular word wins
 * - If none of the above match, the word is left as-is
 */
class RulesetInflector implements WordInflector
{
    /** @var Ruleset[] */
    private $rulesets;

    public function __construct(Ruleset $ruleset, Ruleset ...$rulesets)
    {
        $this->rulesets = array_merge([$ruleset], $rulesets);
    }

    public function inflect(string $word): string
    {
        if ($word === '') {
            return '';
        }

        foreach ($this->rulesets as $ruleset) {
            if ($ruleset->getUninflected()->matches($word)) {
                return $word;
            }

            $inflected = $ruleset->getIrregular()->inflect($word);

            if ($inflected !== $word) {
                return $inflected;
            }

            $inflected = $ruleset->getRegular()->inflect($word);

            if ($inflected !== $word) {
                return $inflected;
            }
        }

        return $word;
    }
}
