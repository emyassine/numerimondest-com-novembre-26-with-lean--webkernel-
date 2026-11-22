<?php

namespace Webkernel\Platform\Forms\Commands\FileGenerators;

use Webkernel\GenerateCode;

class RichContentCustomBlockClassGenerator
{
    public function __construct(
        protected string $fqn,
        protected string $view,
        protected string $previewView,
    ) {}

    public function generate(string $destination): string
    {
        $basename = class_basename($this->fqn);
        $base_label = (string) str($basename)
            ->whenEndsWith('Block', fn ($name) => $name->beforeLast('Block'));
        $id = (string) str($base_label)->snake()->lower();
        $label = (string) str($base_label)->kebab()->replace('-', ' ')->ucfirst();
        $modal_label = (string) str($basename)->kebab()->replace('-', ' ');

        return GenerateCode::make(
            __DIR__ . '/../../../stubs/RichContentCustomBlock.stub',
            $destination,
            [
                'namespace' => (string) str($this->fqn)->beforeLast('\\'),
                'class' => $basename,
                'id' => $id,
                'label' => $label,
                'modal_label' => $modal_label,
                'preview_view' => $this->previewView,
                'view' => $this->view,
            ],
        );
    }
}
