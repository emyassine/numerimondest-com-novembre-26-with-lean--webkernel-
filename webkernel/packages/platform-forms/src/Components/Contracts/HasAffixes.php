<?php

namespace Webkernel\Platform\Forms\Components\Contracts;

use BackedEnum;
use Webkernel\Platform\Schemas\Components\Contracts\HasAffixActions;
use Webkernel\View\Htmlable;

interface HasAffixes extends HasAffixActions
{
    public function getPrefixLabel(): string | Htmlable | null;

    public function getPrefixIcon(): string | BackedEnum | Htmlable | null;

    /**
     * @return string | array<string> | null
     */
    public function getPrefixIconColor(): string | array | null;

    public function getSuffixLabel(): string | Htmlable | null;

    public function getSuffixIcon(): string | BackedEnum | Htmlable | null;

    /**
     * @return string | array<string> | null
     */
    public function getSuffixIconColor(): string | array | null;

    public function isPrefixInline(): bool;

    public function isSuffixInline(): bool;
}
