<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Compiler;

use Webkernel\Platform\Runtime\Ability;

/** A static template subtree gated by a build-known ability mask. */
final readonly class AbilityBlock implements TemplateNode
{
    /** @param non-empty-list<Ability> $abilities @param list<TemplateNode> $children */
    public function __construct(public array $abilities, public array $children)
    {
        if ($abilities === []) {
            throw new \InvalidArgumentException('An ability block must require at least one ability.');
        }
    }
}
