<?php

namespace Webkernel\Platform\Tables\Columns\Layout;

use Closure;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\HasFromBreakpoint;
use Webkernel\Platform\Tables\Columns\Column;

class Split extends Component implements HasEmbeddedView
{
    use HasFromBreakpoint;

    /**
     * @param  array<Column | Component> | Closure  $schema
     */
    final public function __construct(array | Closure $schema)
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Column | Component> | Closure  $schema
     */
    public static function make(array | Closure $schema): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    public function toEmbeddedHtml(): string
    {
        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-ta-split',
                filled($fromBreakpoint = $this->getFromBreakpoint()) ? "{$fromBreakpoint}:fi-ta-split" : 'default:fi-ta-split',
            ]);

        $record = $this->getRecord();
        $recordKey = $this->getRecordKey();
        $rowLoop = $this->getRowLoop();

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?php foreach ($this->getComponents() as $component) { ?>
                <?= $component->record($record)->recordKey($recordKey)->rowLoop($rowLoop)->renderInLayout() ?>
            <?php } ?>
        </div>

        <?php return ob_get_clean();
    }
}
