<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\Platform\Support\Enums\FontWeight;
use Webkernel\ORM\Model;

trait HasWeight
{
    protected FontWeight | string | Closure | null $weight = null;

    public function weight(FontWeight | string | Closure | null $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight(mixed $state = null, ?Model $relatedRecord = null): FontWeight | string | null
    {
        $weight = $this->evaluate($this->weight, [
            'state' => $state,
            'relatedRecord' => $relatedRecord,
        ]);

        if (! is_string($weight)) {
            return $weight;
        }

        return FontWeight::tryFrom($weight) ?? $weight;
    }

    public function hasWeight(): bool
    {
        return $this->weight !== null;
    }
}
