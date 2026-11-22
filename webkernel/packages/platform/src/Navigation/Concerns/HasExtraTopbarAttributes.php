<?php

namespace Webkernel\Platform\Navigation\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraTopbarAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraTopbarAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraTopbarAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraTopbarAttributes, strict: true)) {
                return $this;
            }

            $this->extraTopbarAttributes[] = $attributes;
        } else {
            $this->extraTopbarAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraTopbarAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraTopbarAttributes as $extraTopbarAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraTopbarAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraTopbarAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraTopbarAttributes());
    }
}
