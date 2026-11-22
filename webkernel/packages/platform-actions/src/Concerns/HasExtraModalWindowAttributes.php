<?php

namespace Webkernel\Platform\Actions\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraModalWindowAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraModalWindowAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraModalWindowAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraModalWindowAttributes, strict: true)) {
                return $this;
            }

            $this->extraModalWindowAttributes[] = $attributes;
        } else {
            $this->extraModalWindowAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraModalWindowAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraModalWindowAttributes as $extraModalWindowAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraModalWindowAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraModalWindowAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraModalWindowAttributes());
    }
}
