<?php

namespace Webkernel\Platform\Forms\Components\Concerns;

use Closure;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

trait HasPreview
{
    protected string | Closure | null $preview = null;

    public function preview(string | Closure | null $preview): static
    {
        $this->preview = $preview;

        return $this;
    }

    public function hasPreview(): bool
    {
        return (bool) filled($this->evaluate($this->preview));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function renderPreview(array $data): View|ViewResult
    {
        return \Webkernel\View\View::resolve(
            $this->evaluate($this->preview),
            $data,
        );
    }
}
