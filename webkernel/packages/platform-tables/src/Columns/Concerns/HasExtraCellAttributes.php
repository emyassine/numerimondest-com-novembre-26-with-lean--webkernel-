<?php

namespace Webkernel\Platform\Tables\Columns\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraCellAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraCellAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraCellAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraCellAttributes, strict: true)) {
                return $this;
            }

            $this->extraCellAttributes[] = $attributes;
        } else {
            $this->extraCellAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraCellAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraCellAttributes as $extraCellAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraCellAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraCellAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraCellAttributes());
    }

    public function hasDynamicExtraCellAttributes(): bool
    {
        foreach ($this->extraCellAttributes as $attributes) {
            if ($attributes instanceof Closure) {
                return true;
            }
        }

        return false;
    }
}
