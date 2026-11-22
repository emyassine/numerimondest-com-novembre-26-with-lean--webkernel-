<?php

namespace Webkernel\Platform\Schemas\Components\Concerns;

use Closure;

trait HasFieldWrapper
{
    protected string | Closure | null $fieldWrapperView = null;

    public function fieldWrapperView(string | Closure | null $view): static
    {
        $this->fieldWrapperView = $view;

        return $this;
    }

    public function getFieldWrapperView(): string
    {
        return $this->getCustomFieldWrapperView() ??
            $this->getContainer()->getCustomFieldWrapperView() ??
            'webkernel-forms::field-wrapper';
    }

    public function getCustomFieldWrapperView(): ?string
    {
        return $this->evaluate($this->fieldWrapperView);
    }
}
