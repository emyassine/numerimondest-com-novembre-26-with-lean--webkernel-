<?php

namespace Webkernel\Platform\Tables\Columns\Concerns;

use Closure;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\AttributeBag;

trait HasExtraHeaderAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraHeaderAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraHeaderAttributes(array | Closure $attributes, bool $merge = false): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        if ($merge) {
            if (($attributes instanceof Closure) && in_array($attributes, $this->extraHeaderAttributes, strict: true)) {
                return $this;
            }

            $this->extraHeaderAttributes[] = $attributes;
        } else {
            $this->extraHeaderAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraHeaderAttributes(): array
    {
        $temporaryAttributeBag = new PlatformComponentAttributeBag;

        foreach ($this->extraHeaderAttributes as $extraHeaderAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraHeaderAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraHeaderAttributeBag(): ComponentAttributeBag
    {
        return new PlatformComponentAttributeBag($this->getExtraHeaderAttributes());
    }
}
