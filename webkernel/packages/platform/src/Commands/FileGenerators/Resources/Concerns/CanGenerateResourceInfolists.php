<?php

namespace Webkernel\Platform\Commands\FileGenerators\Resources\Concerns;

use Webkernel\Platform\Infolists\Commands\FileGenerators\Concerns\CanGenerateModelInfolists;
use Webkernel\Platform\Infolists\Components\TextEntry;
use Webkernel\ORM\Model;
use Webkernel\CodeGenerator\Literal;

trait CanGenerateResourceInfolists
{
    use CanGenerateModelInfolists;

    /**
     * @param  ?class-string<Model>  $model
     * @param  array<string>  $exceptColumns
     */
    public function generateInfolistMethodBody(?string $model = null, array $exceptColumns = []): string
    {
        return <<<PHP
            return \$schema
                ->components([
                    {$this->outputInfolistComponents($model, $exceptColumns)}
                ]);
            PHP;
    }

    /**
     * @param  ?class-string<Model>  $model
     * @param  array<string>  $exceptColumns
     */
    public function outputInfolistComponents(?string $model = null, array $exceptColumns = []): string
    {
        $components = $this->getInfolistComponents($model, $exceptColumns);

        if (empty($components)) {
            $recordTitleAttribute = $this->getRecordTitleAttribute();

            if (blank($recordTitleAttribute)) {
                return '//';
            }

            $this->importUnlessPartial(TextEntry::class);

            return new Literal(<<<PHP
                {$this->simplifyFqn(TextEntry::class)}::make(?),
                PHP, [$recordTitleAttribute]);
        }

        return implode(PHP_EOL . '        ', $components);
    }
}
