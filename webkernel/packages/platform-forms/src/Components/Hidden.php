<?php

namespace Webkernel\Platform\Forms\Components;

use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;

class Hidden extends Field implements HasEmbeddedView
{
    protected ?string $publishedViewOverrideCheckPath = 'webkernel-forms::components.hidden';

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpan(['default' => 'hidden']);
    }

    public function toEmbeddedHtml(): string
    {
        $attributes = (new PlatformComponentAttributeBag)
            ->merge([
                'id' => $this->getId(),
                'type' => 'hidden',
                $this->applyStateBindingModifiers('wire:model') => $this->getStatePath(),
            ], escape: false)
            ->merge($this->getExtraAttributes(), escape: false)
            ->class(['fi-fo-hidden']);

        return '<input ' . $attributes->toHtml() . ' />';
    }
}
