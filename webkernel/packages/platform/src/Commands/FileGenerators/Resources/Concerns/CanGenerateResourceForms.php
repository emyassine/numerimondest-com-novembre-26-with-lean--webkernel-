<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Concerns;

use Webkernel\Platform\Forms\Commands\FileGenerators\Concerns\CanGenerateModelForms;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\ORM\Model;
use Webkernel\CodeGenerator\Literal;

trait CanGenerateResourceForms
{
    use CanGenerateModelForms;

    /**
     * @param  ?class-string<Model>  $model
     * @param  array<string>  $exceptColumns
     */
    public function outputFormComponents(?string $model = null, array $exceptColumns = []): string
    {
        $components = $this->getFormComponents($model, $exceptColumns);

        if (empty($components)) {
            $recordTitleAttribute = $this->getRecordTitleAttribute();

            if (blank($recordTitleAttribute)) {
                return '//';
            }

            $this->importUnlessPartial(TextInput::class);

            return new Literal(<<<PHP
                {$this->simplifyFqn(TextInput::class)}::make(?)
                            ->required()
                            ->maxLength(255),
                PHP, [$recordTitleAttribute]);
        }

        return implode(PHP_EOL . '        ', $components);
    }
}
