<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Models\Contracts;

use Webkernel\Platform\Forms\Components\RichEditor\RichContentAttribute;

interface HasRichContent
{
    public function getRichContentAttribute(string $attribute): ?RichContentAttribute;

    public function renderRichContent(string $attribute): string;

    public function hasRichContentAttribute(string $attribute): bool;
}
