<?php

namespace Webkernel\Platform\Forms\Components\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraInputAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraInputAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraInputAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraInputAttributes, strict: true)) {
                return $this;
            }

            $this->extraInputAttributes[] = $attributes;
        } else {
            $this->extraInputAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraInputAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraInputAttributes as $extraInputAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraInputAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraInputAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraInputAttributes());
    }
}
