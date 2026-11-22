<?php

namespace Webkernel\Platform\Infolists\Components\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraEntryWrapperAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraEntryWrapperAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraEntryWrapperAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraEntryWrapperAttributes, strict: true)) {
                return $this;
            }

            $this->extraEntryWrapperAttributes[] = $attributes;
        } else {
            $this->extraEntryWrapperAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraEntryWrapperAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraEntryWrapperAttributes as $extraEntryWrapperAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraEntryWrapperAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraEntryWrapperAttributesBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraEntryWrapperAttributes());
    }
}
