<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Schemas\Contracts\HasRenderHookScopes;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Facades\PlatformView;
use Webkernel\Utilities\Arr;

class RenderHook extends Component implements HasEmbeddedView
{
    protected string | Closure $name;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $scopes = null;

    /**
     * @param  string | array<string> | Closure | null  $scopes
     */
    final public function __construct(string | Closure $name, string | array | Closure | null $scopes = null)
    {
        $this->name($name);
        $this->scopes($scopes);
    }

    /**
     * @param  string | array<string> | null  $scopes
     */
    public static function make(string $name, string | array | null $scopes = null): static
    {
        $static = app(static::class, ['name' => $name, 'scopes' => $scopes]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->liberatedFromContainerGrid();
    }

    public function name(string | Closure $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->evaluate($this->name);
    }

    /**
     * @param  string | array<string> | Closure | null  $scopes
     */
    public function scopes(string | array | Closure | null $scopes): static
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getScopes(): array
    {
        $scopes = Arr::wrap($this->evaluate($this->scopes));

        $livewire = $this->getLivewire();

        if ($livewire instanceof HasRenderHookScopes) {
            $scopes = [
                ...$scopes,
                ...$livewire->getRenderHookScopes(),
            ];
        }

        return array_unique($scopes);
    }

    public function toEmbeddedHtml(): string
    {
        return PlatformView::renderHook($this->getName(), scopes: $this->getScopes())->toHtml();
    }
}
