<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Inflection;

class CachedWordInflector implements WordInflector
{
    /** @var WordInflector */
    private $wordInflector;

    /** @var string[] */
    private $cache = [];

    public function __construct(WordInflector $wordInflector)
    {
        $this->wordInflector = $wordInflector;
    }

    public function inflect(string $word): string
    {
        return $this->cache[$word] ?? $this->cache[$word] = $this->wordInflector->inflect($word);
    }
}
