<?php

namespace Webkernel\Platform\Forms\Components\Repeater;

use Closure;
use Webkernel\Platform\Support\Components\Component;
use Webkernel\Platform\Support\Concerns\CanWrapHeader;
use Webkernel\Platform\Support\Concerns\HasAlignment;
use Webkernel\Platform\Support\Concerns\HasVerticalAlignment;
use Webkernel\Platform\Support\Concerns\HasWidth;
use Webkernel\View\Htmlable;

class TableColumn extends Component
{
    use CanWrapHeader;
    use HasAlignment;
    use HasVerticalAlignment;
    use HasWidth;

    protected string $evaluationIdentifier = 'column';

    protected bool | Closure $isHeaderLabelHidden = false;

    protected bool | Closure $isMarkedAsRequired = false;

    public function __construct(protected string | Htmlable | Closure $label) {}

    public static function make(string | Htmlable | Closure $label): static
    {
        $static = app(static::class, ['label' => $label]);

        $static->configure();

        return $static;
    }

    public function hiddenHeaderLabel(bool | Closure $condition = true): static
    {
        $this->isHeaderLabelHidden = $condition;

        return $this;
    }

    public function getLabel(): string | Htmlable
    {
        return $this->evaluate($this->label);
    }

    public function isHeaderLabelHidden(): bool
    {
        return (bool) $this->evaluate($this->isHeaderLabelHidden);
    }

    public function markAsRequired(bool | Closure $condition = true): static
    {
        $this->isMarkedAsRequired = $condition;

        return $this;
    }

    public function isMarkedAsRequired(): bool
    {
        return (bool) $this->evaluate($this->isMarkedAsRequired);
    }
}
