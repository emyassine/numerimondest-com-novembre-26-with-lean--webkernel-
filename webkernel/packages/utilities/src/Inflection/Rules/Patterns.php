<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Inflection\Rules;

use function array_map;
use function implode;
use function preg_match;

class Patterns
{
    /** @var string */
    private $regex;

    public function __construct(Pattern ...$patterns)
    {
        $patterns = array_map(static function (Pattern $pattern): string {
            return $pattern->getPattern();
        }, $patterns);

        $this->regex = '/^(?:' . implode('|', $patterns) . ')$/i';
    }

    public function matches(string $word): bool
    {
        return preg_match($this->regex, $word, $regs) === 1;
    }
}
