<?php

namespace Webkernel\Platform\Support\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraAlpineAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraAlpineAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraAlpineAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraAlpineAttributes, strict: true)) {
                return $this;
            }

            $this->extraAlpineAttributes[] = $attributes;
        } else {
            $this->extraAlpineAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraAlpineAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraAlpineAttributes as $extraAlpineAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraAlpineAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraAlpineAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraAlpineAttributes());
    }
}
