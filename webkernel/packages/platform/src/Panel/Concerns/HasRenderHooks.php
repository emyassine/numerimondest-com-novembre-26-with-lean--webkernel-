<?php

namespace Webkernel\Platform\Panel\Concerns;

use Closure;
use Webkernel\Platform\Support\Facades\PlatformView;

trait HasRenderHooks
{
    /**
     * @var array<string, array<string, array<Closure>>>
     */
    protected array $renderHooks = [];

    /**
     * @param  string | array<string> | null  $scopes
     */
    public function renderHook(string $name, Closure $hook, string | array | null $scopes = null): static
    {
        if ($scopes === null) {
            $scopes = [''];
        }

        if (! is_array($scopes)) {
            $scopes = [$scopes];
        }

        foreach ($scopes as $scopeName) {
            $this->renderHooks[$name][$scopeName][] = $hook;
        }

        return $this;
    }

    protected function registerRenderHooks(): void
    {
        foreach ($this->renderHooks as $hookName => $scopedHooks) {
            foreach ($scopedHooks as $scope => $hooks) {
                foreach ($hooks as $hook) {
                    PlatformView::registerRenderHook($hookName, $hook, $scope);
                }
            }
        }
    }
}
