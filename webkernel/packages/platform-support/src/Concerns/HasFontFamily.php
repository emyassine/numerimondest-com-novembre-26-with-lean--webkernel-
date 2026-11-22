<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\Platform\Support\Enums\FontFamily;
use Webkernel\ORM\Model;

trait HasFontFamily
{
    protected FontFamily | string | Closure | null $fontFamily = null;

    public function fontFamily(FontFamily | string | Closure | null $family): static
    {
        $this->fontFamily = $family;

        return $this;
    }

    public function getFontFamily(mixed $state = null, ?Model $relatedRecord = null): FontFamily | string | null
    {
        $family = $this->evaluate($this->fontFamily, [
            'state' => $state,
            'relatedRecord' => $relatedRecord,
        ]);

        if (is_string($family)) {
            $family = FontFamily::tryFrom($family) ?? $family;
        }

        return $family;
    }

    public function hasFontFamily(): bool
    {
        return $this->fontFamily !== null;
    }
}
