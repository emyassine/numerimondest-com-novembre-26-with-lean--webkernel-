<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Tables\Contracts\HasTable;
use LogicException;
use Webkernel\View\ViewResult;

class EmbeddedTable extends Component
{
    /**
     * @param  array<string, mixed> | Closure  $livewireComponentData
     */
    public static function make(string | Closure | null $livewireComponent = null, array | Closure $livewireComponentData = []): static | Livewire
    {
        if (filled($livewireComponent)) {
            return Livewire::make($livewireComponent, $livewireComponentData);
        }

        $static = app(static::class);
        $static->configure();

        return $static;
    }

    public function render(): ViewResult
    {
        $livewire = $this->getLivewire();

        if (! ($livewire instanceof HasTable)) {
            throw new LogicException('The [' . $liveview::class . '] component must have a table defined.');
        }

        return $livewire->getTable()->render();
    }
}
